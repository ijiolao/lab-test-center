<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\OrderConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the listener may be attempted
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create the event listener
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Only send confirmation for paid orders
        if ($order->payment_status !== 'paid') {
            return;
        }

        try {
            Mail::to($order->user->email)
                ->send(new OrderConfirmation($order));

            Log::info('Order confirmation email sent', [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]);

            activity()
                ->performedOn($order)
                ->log('Order confirmation email sent');

        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        Log::error('Order confirmation listener failed', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}