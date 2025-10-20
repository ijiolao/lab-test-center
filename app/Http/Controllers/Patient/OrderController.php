<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Test;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->middleware(['auth', 'verified']);
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of patient's orders
     */
    public function index(Request $request)
    {
        $query = auth()->user()->orders()
            ->with('items.test')
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Pagination with limit
        $perPage = min($request->input('per_page', 10), 50);
        $orders = $query->paginate($perPage);

        return view('patient.orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new order
     */
    public function create()
    {
        $tests = Test::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return view('patient.orders.create', compact('tests'));
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tests' => 'required|array|min:1|max:20',
            'tests.*' => 'exists:tests,id',
            'collection_date' => 'required|date|after_or_equal:today|before:' . now()->addMonths(3)->format('Y-m-d'),
            'collection_time' => 'required',
            'special_instructions' => 'nullable|string|max:500',
        ]);

        // Sanitize special instructions
        if (isset($validated['special_instructions'])) {
            $validated['special_instructions'] = strip_tags($validated['special_instructions']);
        }

        try {
            $order = $this->orderService->createOrder(
                auth()->id(),
                $validated['tests'],
                [
                    'collection_date' => $validated['collection_date'],
                    'collection_time' => $validated['collection_time'],
                    'special_instructions' => $validated['special_instructions'] ?? null,
                ]
            );

            Log::info('Patient order created', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('patient.orders.show', $order)
                ->with('success', 'Order created successfully. Please complete payment.');

        } catch (\Exception $e) {
            Log::error('Failed to create patient order', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            $errorMessage = config('app.debug')
                ? 'Failed to create order: ' . $e->getMessage()
                : 'Failed to create order. Please try again.';

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

        $order->load(['items.test', 'result']);

        return view('patient.orders.show', compact('order'));
    }

    /**
     * Show payment form
     */
    public function paymentForm(Order $order)
    {
        $this->authorize('update', $order);

        if ($order->payment_status === 'paid') {
            return redirect()
                ->route('patient.orders.show', $order)
                ->with('info', 'This order has already been paid');
        }

        return view('patient.orders.payment', compact('order'));
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            $this->orderService->processPayment($order, $request->payment_method_id);

            Log::info('Patient payment processed', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
            ]);

            return redirect()
                ->route('patient.orders.show', $order)
                ->with('success', 'Payment successful! Your order has been confirmed.');

        } catch (\Exception $e) {
            Log::error('Patient payment failed', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            $errorMessage = 'Payment failed. Please check your card details and try again.';

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Cancel an order
     */
    public function cancel(Request $request, Order $order)
    {
        $this->authorize('cancel', $order);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        // Sanitize reason
        $reason = $request->reason ? strip_tags($request->reason) : 'Cancelled by patient';

        try {
            $this->orderService->cancelOrder($order, $reason);

            return redirect()
                ->route('patient.orders.index')
                ->with('success', 'Order cancelled successfully');

        } catch (\Exception $e) {
            Log::error('Failed to cancel patient order', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Download order receipt (PDF)
     */
    public function downloadReceipt(Order $order)
    {
        $this->authorize('view', $order);

        if ($order->payment_status !== 'paid') {
            return back()->with('error', 'Receipt not available for unpaid orders');
        }

        try {
            // Generate and return PDF receipt
            $pdf = Pdf::loadView('pdfs.receipt', compact('order'));

            return $pdf->download("receipt_{$order->order_number}.pdf");

        } catch (\Exception $e) {
            Log::error('Failed to generate receipt', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to generate receipt. Please try again.');
        }
    }
}