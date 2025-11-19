<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RecordController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:view-admin-dashboard']);
    }

    /**
     * Display a listing of patient records
     */
    public function index(Request $request)
    {
        $query = User::patients()
            ->withCount(['orders', 'orders as completed_orders_count' => function($q) {
                $q->where('status', 'completed');
            }]);

        // Search
        if ($request->has('search') && $request->search) {
            $search = strip_tags($request->search);
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Date range filter
        if ($request->has('registered_from')) {
            try {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($request->registered_from));
            } catch (\Exception $e) {
                // Skip invalid date
            }
        }

        if ($request->has('registered_to')) {
            try {
                $query->where('created_at', '<=', \Carbon\Carbon::parse($request->registered_to));
            } catch (\Exception $e) {
                // Skip invalid date
            }
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (in_array($sortBy, ['created_at', 'first_name', 'last_name', 'email'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Pagination with limit
        $perPage = min($request->input('per_page', 20), 100);
        $patients = $query->paginate($perPage);

        // Statistics
        $stats = [
            'total_patients' => User::patients()->count(),
            'active_patients' => User::patients()->where('is_active', true)->count(),
            'new_this_month' => User::patients()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'with_orders' => User::patients()
                ->whereHas('orders')
                ->count(),
        ];

        return view('admin.records.index', compact('patients', 'stats'));
    }

    /**
     * Display the specified patient record
     */
    public function show(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        // Load relationships
        $patient->loadCount(['orders', 'orders as completed_orders_count' => function($q) {
            $q->where('status', 'completed');
        }]);

        // Get orders with pagination
        $orders = $patient->orders()
            ->with('items.test')
            ->latest()
            ->paginate(10);

        // Get results
        $results = Result::forPatient($patient->id)
            ->with('order.items')
            ->latest('result_date')
            ->limit(10)
            ->get();

        // Statistics
        $stats = [
            'total_orders' => $patient->orders()->count(),
            'total_spent' => $patient->orders()
                ->where('payment_status', 'paid')
                ->sum('total'),
            'completed_tests' => $patient->orders()
                ->where('status', 'completed')
                ->withCount('items')
                ->get()
                ->sum('items_count'),
            'pending_results' => $patient->orders()
                ->whereIn('status', ['sent_to_lab', 'processing'])
                ->count(),
        ];

        // Activity log
        $activityLog = activity()
            ->forSubject($patient)
            ->with('causer')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.records.show', compact('patient', 'orders', 'results', 'stats', 'activityLog'));
    }

    /**
     * Show the form for editing the specified patient
     */
    public function edit(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        return view('admin.records.edit', compact('patient'));
    }

    /**
     * Update the specified patient
     */
    public function update(Request $request, User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $patient->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_postcode' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:2',
            'is_active' => 'boolean',
        ]);

        try {
            // Build address array
            $address = null;
            if ($request->filled('address_line1')) {
                $address = [
                    'line1' => $request->address_line1,
                    'line2' => $request->address_line2,
                    'city' => $request->address_city,
                    'postcode' => $request->address_postcode,
                    'country' => $request->address_country ?? 'GB',
                ];
            }

            $patient->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'address' => $address,
                'is_active' => $request->has('is_active'),
            ]);

            activity()
                ->performedOn($patient)
                ->withProperties($validated)
                ->log('Patient record updated by admin');

            Log::info('Patient record updated', [
                'patient_id' => $patient->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.records.show', $patient)
                ->with('success', 'Patient record updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update patient record', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = config('app.debug')
                ? 'Failed to update record: ' . $e->getMessage()
                : 'Failed to update record. Please try again.';

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * Show form to reset patient password
     */
    public function resetPasswordForm(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        return view('admin.records.reset-password', compact('patient'));
    }

    /**
     * Reset patient password
     */
    public function resetPassword(Request $request, User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        $request->validate([
            'password' => 'required|confirmed|min:8',
        ]);

        try {
            $patient->update([
                'password' => Hash::make($request->password),
            ]);

            activity()
                ->performedOn($patient)
                ->log('Password reset by admin');

            Log::info('Patient password reset by admin', [
                'patient_id' => $patient->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.records.show', $patient)
                ->with('success', 'Password reset successfully');

        } catch (\Exception $e) {
            Log::error('Failed to reset patient password', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to reset password. Please try again.');
        }
    }

    /**
     * Toggle patient active status
     */
    public function toggleActive(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        try {
            $patient->update(['is_active' => !$patient->is_active]);

            $status = $patient->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($patient)
                ->log("Patient account {$status}");

            Log::info("Patient account {$status}", [
                'patient_id' => $patient->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', "Patient account {$status} successfully");

        } catch (\Exception $e) {
            Log::error('Failed to toggle patient status', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update status. Please try again.');
        }
    }

    /**
     * Display patient order history
     */
    public function orders(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        $orders = $patient->orders()
            ->with(['items.test', 'result'])
            ->latest()
            ->paginate(20);

        return view('admin.records.orders', compact('patient', 'orders'));
    }

    /**
     * Display patient result history
     */
    public function results(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        $results = Result::forPatient($patient->id)
            ->with('order.items')
            ->latest('result_date')
            ->paginate(20);

        return view('admin.records.results', compact('patient', 'results'));
    }

    /**
     * Export patient data (GDPR compliance)
     */
    public function exportData(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        try {
            // Collect all patient data
            $data = [
                'personal_information' => [
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'email' => $patient->email,
                    'phone' => $patient->phone,
                    'date_of_birth' => $patient->date_of_birth->format('Y-m-d'),
                    'gender' => $patient->gender,
                    'address' => $patient->address,
                    'registered_at' => $patient->created_at->toISOString(),
                ],
                'orders' => $patient->orders()->with('items')->get()->map(function($order) {
                    return [
                        'order_number' => $order->order_number,
                        'date' => $order->created_at->toISOString(),
                        'status' => $order->status,
                        'total' => $order->total,
                        'tests' => $order->items->pluck('test_name'),
                    ];
                }),
                'results' => Result::forPatient($patient->id)->get()->map(function($result) {
                    return [
                        'result_date' => $result->result_date->toISOString(),
                        'order_number' => $result->order->order_number,
                        'has_critical_values' => $result->has_critical_values,
                        'viewed_at' => $result->patient_viewed_at?->toISOString(),
                    ];
                }),
            ];

            activity()
                ->performedOn($patient)
                ->log('Patient data exported by admin');

            $filename = "patient_data_{$patient->id}_" . now()->format('Y-m-d_His') . ".json";

            return response()->json($data, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            Log::error('Failed to export patient data', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to export data. Please try again.');
        }
    }

    /**
     * Merge duplicate patient records
     */
    public function mergeForm(User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        // Find potential duplicates
        $duplicates = User::patients()
            ->where('id', '!=', $patient->id)
            ->where(function($query) use ($patient) {
                $query->where('email', $patient->email)
                      ->orWhere(function($q) use ($patient) {
                          $q->where('first_name', $patient->first_name)
                            ->where('last_name', $patient->last_name)
                            ->where('date_of_birth', $patient->date_of_birth);
                      });
            })
            ->withCount('orders')
            ->get();

        return view('admin.records.merge', compact('patient', 'duplicates'));
    }

    /**
     * Execute patient record merge
     */
    public function merge(Request $request, User $patient)
    {
        if (!$patient->isPatient()) {
            abort(404);
        }

        $request->validate([
            'merge_with' => 'required|exists:users,id',
        ]);

        $duplicatePatient = User::findOrFail($request->merge_with);

        if (!$duplicatePatient->isPatient() || $duplicatePatient->id === $patient->id) {
            return back()->with('error', 'Invalid merge target');
        }

        try {
            DB::transaction(function() use ($patient, $duplicatePatient) {
                // Move all orders to primary patient
                $duplicatePatient->orders()->update(['user_id' => $patient->id]);

                // Log the merge
                activity()
                    ->performedOn($patient)
                    ->withProperties([
                        'merged_patient_id' => $duplicatePatient->id,
                        'merged_patient_email' => $duplicatePatient->email,
                    ])
                    ->log('Patient records merged');

                // Soft delete duplicate
                $duplicatePatient->delete();
            });

            Log::info('Patient records merged', [
                'primary_patient_id' => $patient->id,
                'duplicate_patient_id' => $duplicatePatient->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.records.show', $patient)
                ->with('success', 'Patient records merged successfully');

        } catch (\Exception $e) {
            Log::error('Failed to merge patient records', [
                'patient_id' => $patient->id,
                'duplicate_id' => $request->merge_with,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to merge records. Please try again.');
        }
    }
}