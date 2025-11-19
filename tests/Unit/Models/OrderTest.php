<?php

namespace Tests\Unit\Models;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_collected_updates_status_and_dispatches_event(): void
    {
        Event::fake();

        $collector = User::factory()->create();
        $order = Order::factory()->create([
            'status' => 'paid',
            'collection_date' => now(),
            'collection_time' => now(),
        ]);

        $result = $order->markAsCollected($collector->id);

        $this->assertTrue($result);

        $order->refresh();
        $this->assertSame('collected', $order->status);
        $this->assertNotNull($order->collected_at);
        $this->assertSame($collector->id, $order->collected_by);

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event) use ($order) {
            return $event->order->is($order) && $event->previousStatus === 'paid';
        });
    }
}
