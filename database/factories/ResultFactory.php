<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'result_date' => now(),
            'pdf_path' => null,
            'parsed_data' => [
                'tests' => [
                    [
                        'test_code' => 'CBC',
                        'test_name' => 'Complete Blood Count',
                        'value' => '10',
                        'unit' => 'g/dL',
                    ],
                ],
            ],
            'has_critical_values' => false,
            'is_reviewed' => true,
            'patient_notified_at' => now(),
            'patient_viewed_at' => null,
        ];
    }
}
