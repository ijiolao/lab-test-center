<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Test;
use App\Models\OrderItem;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Create a new order with test items
     *
     * @param int $userId
     * @param array $testIds
     * @param array $orderData
     * @return Order
     * @throws \Exception
     */
    public function createOrder(int $userId, array $testIds, array $orderData = []): Order
    {
        return DB::transaction(function () use ($userId, $testIds, $orderData) {
            // Get tests and calculate pricing
            $tests = Test::whereIn('id', $testIds)->get();
            
            if ($tests->isEmpty()) {
                throw new \Exception('No valid tests selected');
            }

            $subtotal = $tests->sum('price');
            $taxRate = config('app.tax_rate', 0.20);
            $tax = round($subtotal * $taxRate, 2);
            $total = $subtotal + $tax;

            // Create order
            $order = Order::create([
                'user_id' => $userId,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending_payment',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'collection_date' => $orderData['collection_date'] ?? null,
                'collection_time' => $orderData['collection_time'] ?? null,
                'collection_location' => $orderData['collection_location'] ?? config('app.default_location'),
                'special_instructions' => $orderData['special_instructions'] ?? null,
            ]);

            // Create order items with specimen barcodes
            foreach ($tests as $test) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'test_id' => $test->id,
                    'test_name' => $test->name,
                    'test_code' => $test->code,
                    'price' => $test->price,
                    'specimen_barcode' => $this->generateSpecimenBarcode($order->order_number, $test->code),
                ]);
            }

            // Reload with items
            $order->load('items.test', 'user');

            // Fire event
            event(new OrderCreated($order));

            Log::info('Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $userId,
                'total' => $total,
            ]);

            return $order;
        });
    }

    /**
     * Process payment for an order
     *
     * @param Order $order
     * @param string $paymentMethodId
     * @return bool
     * @throws \Exception
     */
    public function processPayment(Order $order, string $paymentMethodId): bool
    {
        if ($order->payment_status === 'paid') {
            throw new \Exception('Order already paid');
        }

        try {
            $user = $order->user;

            // Create Stripe payment intent with idempotency key
            $paymentIntent = $user->charge(
                $order->total * 100, // Convert to pence/cents
                $paymentMethodId,
                [
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ],
                ],
                [
                    'idempotency_key' => 'order_' . $order->id . '_payment'
                ]
            );

            // Update order
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'payment_intent_id' => $paymentIntent->id,
                'status' => $order->collection_date ? 'scheduled' : 'paid',
            ]);

            event(new OrderStatusChanged($order, 'pending_payment'));

            Log::info('Payment processed successfully', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return true;

        } catch (\Exception $e) {
            $order->update(['payment_status' => 'failed']);

            Log::error('Payment processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark order as collected
     *
     * @param Order $order
     * @param int|null $collectedBy
     * @return bool
     */
    public function markAsCollected(Order $order, ?int $collectedBy = null): bool
    {
        if (!in_array($order->status, ['paid', 'scheduled'])) {
            throw new \Exception('Order cannot be collected in current status');
        }

        $previousStatus = $order->status;

        $order->update([
            'status' => 'collected',
            'collected_at' => now(),
            'collected_by' => $collectedBy ?? auth()->id(),
        ]);

        event(new OrderStatusChanged($order, $previousStatus));

        Log::info('Order marked as collected', [
            'order_id' => $order->id,
            'collected_by' => $order->collected_by,
        ]);

        return true;
    }

    /**
     * Cancel an order
     *
     * @param Order $order
     * @param string|null $reason
     * @return bool
     */
    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled in current status');
        }

        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $reason, $previousStatus) {
            $order->update(['status' => 'cancelled']);

            // Process refund if paid
            if ($order->payment_status === 'paid' && $order->payment_intent_id) {
                $this->processRefund($order);
            }

            if ($reason) {
                activity()
                    ->performedOn($order)
                    ->withProperties(['reason' => $reason])
                    ->log('Order cancelled');
            }

            event(new OrderStatusChanged($order, $previousStatus));
        });

        Log::info('Order cancelled', [
            'order_id' => $order->id,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Process refund for an order
     *
     * @param Order $order
     * @return bool
     */
    protected function processRefund(Order $order): bool
    {
        try {
            $order->user->refund($order->payment_intent_id);

            $order->update(['payment_status' => 'refunded']);

            Log::info('Refund processed', [
                'order_id' => $order->id,
                'payment_intent_id' => $order->payment_intent_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate unique order number with race condition protection
     *
     * @return string
     */
    protected function generateOrderNumber(): string
    {
        return DB::transaction(function () {
            $year = date('Y');
            
            // Lock the latest order to prevent race conditions
            $lastOrder = Order::whereYear('created_at', $year)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            $number = $lastOrder ? intval(substr($lastOrder->order_number, -6)) + 1 : 1;

            return 'ORD-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate unique specimen barcode with collision detection
     *
     * @param string $orderNumber
     * @param string $testCode
     * @return string
     */
    protected function generateSpecimenBarcode(string $orderNumber, string $testCode): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $barcode = strtoupper(
                $orderNumber . '-' .
                $testCode . '-' .
                substr(md5(uniqid(mt_rand(), true)), 0, 6)
            );

            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \Exception('Failed to generate unique specimen barcode after ' . $maxAttempts . ' attempts');
            }

        } while (OrderItem::where('specimen_barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Get orders for today's collection
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodaysOrders()
    {
        return Order::with(['user', 'items.test'])
            ->today()
            ->whereIn('status', ['paid', 'scheduled', 'collected'])
            ->orderBy('collection_time')
            ->get();
    }

    /**
     * Get orders awaiting lab submission
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrdersAwaitingLabSubmission()
    {
        return Order::with(['user', 'items.test'])
            ->awaitingLabSubmission()
            ->get();
    }

    /**
     * Get order statistics
     *
     * @param \Carbon\Carbon|null $date
     * @return array
     */
    public function getStatistics($date = null)
    {
        $date = $date ?? today();

        return [
            'today_total' => Order::whereDate('collection_date', $date)->count(),
            'pending_collection' => Order::whereDate('collection_date', $date)
                ->whereIn('status', ['paid', 'scheduled'])
                ->count(),
            'collected_today' => Order::whereDate('collected_at', $date)->count(),
            'pending_results' => Order::where('status', 'processing')->count(),
            'awaiting_lab_submission' => Order::awaitingLabSubmission()->count(),
        ];
    }
}