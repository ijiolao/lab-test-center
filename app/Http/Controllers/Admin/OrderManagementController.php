<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Test;
use App\Models\LabPartner;
use App\Services\OrderService;
use App\Services\PrintService;
use App\Jobs\SubmitOrderToLab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderManagementController extends Controller
{
    protected $orderService;
    protected $printService;

    public function __construct(OrderService $orderService)
    {
        $this->middleware('auth');
        $this->orderService = $orderService;
        
        // Conditionally resolve PrintService if available
        if (app()->bound(PrintService::class)) {
            $this->printService = app(PrintService::class);
        }
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['user', 'items.test'])
            ->latest('collection_date');

        // Filter by date with validation
        if ($request->has('date')) {
            try {
                $date = \Carbon\Carbon::parse($request->date);
                $query->whereDate('collection_date', $date);
            } catch (\Exception $e) {
                // Invalid date, default to today
                $query->whereDate('collection_date', today());
            }
        } else {
            $query->whereDate('collection_date', today());
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status') && $request->payment_status != '') {
            $query->where('payment_status', $request->payment_status);
        }

        // Search with input sanitization
        if ($request->has('search') && $request->search) {
            $search = strip_tags($request->search);
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Pagination with limit
        $perPage = min($request->input('per_page', 20), 100);
        $orders = $query->paginate($perPage);

        // Statistics
        $dateFilter = $request->date ?? today();
        $stats = $this->orderService->getStatistics($dateFilter);

        return view('admin.orders.index', compact('orders', 'stats'));
    }

    /**
     * Show the form for creating a new order
     */
    public function create()
    {
        $this->authorize('create', Order::class);

        $tests = Test::active()->orderBy('category')->orderBy('name')->get()->groupBy('category');
        
        return view('admin.orders.create', compact('tests'));
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'email' => 'required_without:user_id|email',
            'first_name' => 'required_without:user_id|string|max:255',
            'last_name' => 'required_without:user_id|string|max:255',
            'date_of_birth' => 'required_without:user_id|date|before:today',
            'phone' => 'required_without:user_id|string|max:20',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'tests' => 'required|array|min:1',
            'tests.*' => 'exists:tests,id',
            'collection_date' => 'required|date|after_or_equal:today',
            'collection_time' => 'required',
            'collection_location' => 'nullable|string|max:255',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        // Sanitize special instructions
        if (isset($validated['special_instructions'])) {
            $validated['special_instructions'] = strip_tags($validated['special_instructions']);
        }

        try {
            // Wrap in transaction
            $order = DB::transaction(function () use ($request, $validated) {
                // Create or get user
                if (!$request->user_id) {
                    $user = User::firstOrCreate(
                        ['email' => $validated['email']],
                        [
                            'first_name' => $validated['first_name'],
                            'last_name' => $validated['last_name'],
                            'date_of_birth' => $validated['date_of_birth'],
                            'phone' => $validated['phone'],
                            'gender' => $validated['gender'],
                            'password' => bcrypt(Str::random(32)),
                            'role' => 'patient',
                        ]
                    );
                } else {
                    $user = User::findOrFail($request->user_id);
                }

                // Create order
                $order = $this->orderService->createOrder(
                    $user->id,
                    $validated['tests'],
                    [
                        'collection_date' => $validated['collection_date'],
                        'collection_time' => $validated['collection_time'],
                        'collection_location' => $validated['collection_location'],
                        'special_instructions' => $validated['special_instructions'] ?? null,
                    ]
                );

                // Mark as paid (manual orders are pre-paid)
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'manual',
                    'status' => 'scheduled',
                ]);

                return $order;
            });

            activity()
                ->performedOn($order)
                ->log('Manual order created by admin');

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', 'Order created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user_id ?? 'new',
            ]);

            $errorMessage = config('app.debug')
                ? 'Failed to create order: ' . $e->getMessage()
                : 'Failed to create order. Please try again or contact support.';

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'user',
            'items.test',
            'labSubmissions.labPartner',
            'result.reviewer',
            'collectedBy'
        ]);

        $labPartners = LabPartner::active()->byPriority()->get();

        $activityLog = activity()
            ->forSubject($order)
            ->with('causer') // Fix N+1
            ->latest()
            ->get();

        return view('admin.orders.show', compact('order', 'labPartners', 'activityLog'));
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'collection_date' => 'sometimes|date|after_or_equal:today',
            'collection_time' => 'sometimes',
            'collection_location' => 'nullable|string|max:255',
            'special_instructions' => 'nullable|string|max:1000',
        ]);

        // Sanitize special instructions
        if (isset($validated['special_instructions'])) {
            $validated['special_instructions'] = strip_tags($validated['special_instructions']);
        }

        // Don't allow status updates via mass assignment
        unset($validated['status']);

        try {
            $order->update($validated);

            activity()
                ->performedOn($order)
                ->withProperties($validated)
                ->log('Order updated by admin');

            return back()->with('success', 'Order updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = config('app.debug')
                ? 'Failed to update order: ' . $e->getMessage()
                : 'Failed to update order. Please try again.';

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Mark order as collected
     */
    public function markCollected(Request $request, Order $order)
    {
        $this->authorize('collect', $order);

        try {
            $this->orderService->markAsCollected($order, auth()->id());

            return back()->with('success', 'Order marked as collected');

        } catch (\Exception $e) {
            Log::error('Failed to mark order as collected', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to mark as collected: ' . $e->getMessage());
        }
    }

    /**
     * Print specimen label
     */
    public function printLabel(Request $request, Order $order)
    {
        $this->authorize('print', $order);

        if (!$this->printService) {
            return back()->with('error', 'Print service not configured. Please contact system administrator.');
        }

        try {
            $itemId = $request->input('item_id');

            $this->printService->printSpecimenLabel($order, $itemId);

            activity()
                ->performedOn($order)
                ->log('Specimen label printed');

            return back()->with('success', 'Label printed successfully');

        } catch (\Exception $e) {
            Log::error('Failed to print label', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Print failed: ' . $e->getMessage());
        }
    }

    /**
     * Submit order to lab
     */
    public function submitToLab(Request $request, Order $order)
    {
        $this->authorize('submitToLab', $order);

        $request->validate([
            'lab_partner_id' => 'required|exists:lab_partners,id',
        ]);

        try {
            $labPartner = LabPartner::findOrFail($request->lab_partner_id);

            // Queue the submission
            SubmitOrderToLab::dispatch($order, $labPartner->id);

            activity()
                ->performedOn($order)
                ->withProperties(['lab_partner' => $labPartner->name])
                ->log("Order queued for submission to {$labPartner->name}");

            return back()->with('success', "Order queued for submission to {$labPartner->name}");

        } catch (\Exception $e) {
            Log::error('Failed to queue lab submission', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to submit: ' . $e->getMessage());
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Sanitize reason
        $reason = strip_tags($request->reason);

        try {
            $this->orderService->cancelOrder($order, $reason);

            return back()->with('success', 'Order cancelled successfully');

        } catch (\Exception $e) {
            Log::error('Failed to cancel order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel: ' . $e->getMessage());
        }
    }

    /**
     * Search for patients (AJAX)
     * 
     * @note Requires CSRF token in request headers
     */
    public function searchPatients(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $search = strip_tags($request->input('q', ''));

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $patients = User::patients()
            ->where(function($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'dob' => $user->date_of_birth->format('Y-m-d'),
                    'phone' => $user->phone,
                ];
            });

        return response()->json($patients);
    }

    /**
     * Export orders to CSV
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['user', 'items.test']);

        // Apply same filters as index
        if ($request->has('date')) {
            try {
                $date = \Carbon\Carbon::parse($request->date);
                $query->whereDate('collection_date', $date);
            } catch (\Exception $e) {
                // Skip invalid date
            }
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $orders = $query->get();

        $filename = 'orders_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Order Number',
                'Patient Name',
                'Patient Email',
                'Tests',
                'Collection Date',
                'Status',
                'Payment Status',
                'Total',
                'Created At',
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->user->full_name,
                    $order->user->email,
                    $order->items->pluck('test_name')->implode(', '),
                    $order->collection_date->format('Y-m-d'),
                    $order->status,
                    $order->payment_status,
                    $order->formatted_total,
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}