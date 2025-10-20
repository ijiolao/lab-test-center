<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Mail\OrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('cashier.webhook.secret');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Invalid payload'], 400);
            
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentCanceled($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event', [
                        'type' => $event->type,
                    ]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Handle successful payment
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handlePaymentSuccess($paymentIntent): void
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();

        if (!$order) {
            Log::warning('Payment intent not associated with any order', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        if ($order->payment_status === 'paid') {
            Log::info('Order already marked as paid', [
                'order_id' => $order->id,
            ]);
            return;
        }

        // Update order status
        $order->update([
            'payment_status' => 'paid',
            'status' => $order->collection_date ? 'scheduled' : 'paid',
        ]);

        Log::info('Payment successful', [
            'order_id' => $order->id,
            'amount' => $paymentIntent->amount / 100,
        ]);

        // Send confirmation email
        try {
            Mail::to($order->user->email)->send(new OrderConfirmation($order));
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Log activity
        activity()
            ->performedOn($order)
            ->withProperties([
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount / 100,
            ])
            ->log('Payment completed successfully');
    }

    /**
     * Handle failed payment
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handlePaymentFailed($paymentIntent): void
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();

        if (!$order) {
            Log::warning('Payment intent not associated with any order', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        $order->update(['payment_status' => 'failed']);

        Log::warning('Payment failed', [
            'order_id' => $order->id,
            'payment_intent_id' => $paymentIntent->id,
            'failure_message' => $paymentIntent->last_payment_error?->message ?? 'Unknown error',
        ]);

        // Log activity
        activity()
            ->performedOn($order)
            ->withProperties([
                'payment_intent_id' => $paymentIntent->id,
                'error' => $paymentIntent->last_payment_error?->message ?? 'Unknown error',
            ])
            ->log('Payment failed');

        // Optional: Notify user of failed payment
        // Mail::to($order->user->email)->send(new PaymentFailed($order));
    }

    /**
     * Handle canceled payment
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handlePaymentCanceled($paymentIntent): void
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();

        if (!$order) {
            return;
        }

        Log::info('Payment canceled', [
            'order_id' => $order->id,
            'payment_intent_id' => $paymentIntent->id,
        ]);

        activity()
            ->performedOn($order)
            ->log('Payment canceled');
    }

    /**
     * Handle refund
     *
     * @param \Stripe\Charge $charge
     * @return void
     */
    protected function handleRefund($charge): void
    {
        $order = Order::where('payment_intent_id', $charge->payment_intent)->first();

        if (!$order) {
            Log::warning('Refunded charge not associated with any order', [
                'charge_id' => $charge->id,
            ]);
            return;
        }

        $order->update(['payment_status' => 'refunded']);

        Log::info('Refund processed', [
            'order_id' => $order->id,
            'charge_id' => $charge->id,
            'amount' => $charge->amount_refunded / 100,
        ]);

        activity()
            ->performedOn($order)
            ->withProperties([
                'charge_id' => $charge->id,
                'amount' => $charge->amount_refunded / 100,
            ])
            ->log('Refund processed');

        // Optional: Notify user of refund
         Mail::to($order->user->email)->send(new RefundProcessed($order));
    }
}