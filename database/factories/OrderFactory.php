<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'status' => 'paid',
            'subtotal' => $this->faker->randomFloat(2, 50, 200),
            'tax' => 0,
            'total' => $this->faker->randomFloat(2, 50, 200),
            'payment_status' => 'paid',
            'payment_method' => 'card',
            'payment_intent_id' => 'pi_' . Str::random(24),
            'collection_date' => now()->addDay()->toDateString(),
            'collection_time' => now()->addDay(),
        ];
    }
}
