<?php

namespace App\Services\LabPartner;

use App\Models\Order;
use App\Models\LabPartner;
use App\Models\LabSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuestDiagnosticsAdapter implements LabPartnerInterface
{
    protected $labPartner;
    protected $client;

    public function __construct(LabPartner $labPartner)
    {
        $this->labPartner = $labPartner;
        $this->client = Http::baseUrl($labPartner->api_endpoint)
                           ->withHeaders([
                               'Authorization' => 'Bearer ' . decrypt($labPartner->api_key),
                               'Content-Type' => 'application/json'
                           ])
                           ->timeout(30);
    }

    public function submitOrder(Order $order): LabSubmission
    {
        $submission = LabSubmission::create([
            'order_id' => $order->id,
            'lab_partner_id' => $this->labPartner->id,
            'status' => 'pending'
        ]);

        try {
            $payload = $this->buildOrderPayload($order);
            
            $submission->update(['request_payload' => $payload]);

            $response = $this->client->post('/orders', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                $submission->update([
                    'status' => 'submitted',
                    'lab_order_id' => $data['orderId'],
                    'response_payload' => $data,
                    'submitted_at' => now()
                ]);

                $order->update(['status' => 'sent_to_lab']);
            } else {
                throw new \Exception($response->body());
            }

        } catch (\Exception $e) {
            Log::error('Quest order submission failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            $submission->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }

        return $submission;
    }

    protected function buildOrderPayload(Order $order): array
    {
        $user = $order->user;
        
        return [
            'patient' => [
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'dateOfBirth' => $user->date_of_birth->format('Y-m-d'),
                'gender' => $user->gender,
                'phone' => $user->phone,
                'email' => $user->email,
            ],
            'orderNumber' => $order->order_number,
            'collectionDate' => $order->collection_date->format('Y-m-d'),
            'tests' => $order->items->map(function($item) {
                return [
                    'testCode' => $item->test_code,
                    'specimenId' => $item->specimen_barcode,
                ];
            })->toArray(),
            'priority' => 'routine'
        ];
    }

    public function getOrderStatus(string $labOrderId): array
    {
        $response = $this->client->get("/orders/{$labOrderId}/status");
        
        return $response->json();
    }

    public function parseResults($rawData): array
    {
        // Parse Quest-specific result format
        return [
            'tests' => collect($rawData['results'])->map(function($result) {
                return [
                    'test_code' => $result['testCode'],
                    'test_name' => $result['testName'],
                    'value' => $result['value'],
                    'unit' => $result['unit'],
                    'reference_range' => $result['referenceRange'],
                    'flag' => $result['abnormalFlag'] ?? null,
                    'status' => $result['status']
                ];
            })->toArray(),
            'result_date' => $rawData['resultDate'],
            'performing_lab' => $rawData['lab']
        ];
    }

    public function validateConnection(): bool
    {
        try {
            $response = $this->client->get('/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSupportedTests(): array
    {
        $response = $this->client->get('/tests');
        
        return $response->json()['tests'] ?? [];
    }
}