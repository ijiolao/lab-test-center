<?php

namespace App\Services\LabPartner;

use App\Models\Order;
use App\Models\LabPartner;
use App\Models\LabSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LabCorp API Adapter
 * 
 * Integration with LabCorp REST API
 */
class LabCorpAdapter implements LabPartnerInterface
{
    protected $labPartner;
    protected $client;

    public function __construct(LabPartner $labPartner)
    {
        $this->labPartner = $labPartner;
        $this->client = $this->buildHttpClient();
    }

    /**
     * Build HTTP client with OAuth authentication
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function buildHttpClient()
    {
        $token = $this->getAccessToken();

        return Http::baseUrl($this->labPartner->api_endpoint)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(config('lab-partners.api_timeout', 30))
            ->retry(3, 100);
    }

    /**
     * Get OAuth access token
     *
     * @return string
     */
    protected function getAccessToken(): string
    {
        // Check cache first
        $cacheKey = "labcorp_token_{$this->labPartner->id}";
        
        if ($cached = cache($cacheKey)) {
            return $cached;
        }

        // Request new token
        $credentials = $this->labPartner->credentials;
        
        $response = Http::asForm()->post($credentials['token_url'] ?? $this->labPartner->api_endpoint . '/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->labPartner->decrypted_api_key,
            'client_secret' => $this->labPartner->decrypted_api_secret,
            'scope' => $credentials['scope'] ?? 'orders.write results.read',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to obtain LabCorp access token');
        }

        $data = $response->json();
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 3600;

        // Cache token (expires 5 minutes before actual expiry)
        cache([$cacheKey => $token], now()->addSeconds($expiresIn - 300));

        return $token;
    }

    /**
     * Submit order to LabCorp
     *
     * @param Order $order
     * @return LabSubmission
     * @throws \Exception
     */
    public function submitOrder(Order $order): LabSubmission
    {
        $submission = LabSubmission::create([
            'order_id' => $order->id,
            'lab_partner_id' => $this->labPartner->id,
            'status' => 'pending',
        ]);

        try {
            $payload = $this->buildOrderPayload($order);

            $submission->update(['request_payload' => $payload]);

            $response = $this->client->post('/api/v2/orders', $payload);

            if ($response->successful()) {
                $data = $response->json();

                $submission->markAsSubmitted(
                    $data['labOrderId'] ?? $data['orderNumber'],
                    $data
                );

                $order->update(['status' => 'sent_to_lab']);

                Log::info('LabCorp order submitted successfully', [
                    'order_id' => $order->id,
                    'lab_order_id' => $data['labOrderId'],
                ]);
            } else {
                throw new \Exception('LabCorp API error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('LabCorp order submission failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $submission->markAsFailed($e->getMessage());

            throw $e;
        }

        return $submission;
    }

    /**
     * Build order payload for LabCorp API
     *
     * @param Order $order
     * @return array
     */
    protected function buildOrderPayload(Order $order): array
    {
        $user = $order->user;

        return [
            'clientOrderNumber' => $order->order_number,
            'patient' => [
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'middleName' => '',
                'dateOfBirth' => $user->date_of_birth->format('Y-m-d'),
                'gender' => $this->mapGender($user->gender),
                'contactInfo' => [
                    'phone' => $user->phone,
                    'email' => $user->email,
                ],
                'address' => $this->formatAddress($user->address),
            ],
            'orderDetails' => [
                'collectionDateTime' => $order->collection_date->format('Y-m-d') . 'T' . 
                                       ($order->collection_time?->format('H:i:s') ?? '09:00:00'),
                'priority' => 'ROUTINE',
                'orderingProvider' => [
                    'npi' => config('lab-partners.labcorp.provider_npi'),
                    'firstName' => config('lab-partners.labcorp.provider_first_name'),
                    'lastName' => config('lab-partners.labcorp.provider_last_name'),
                ],
            ],
            'tests' => $order->items->map(function ($item) {
                return [
                    'testCode' => $this->mapTestCode($item->test_code),
                    'testName' => $item->test_name,
                    'specimenInfo' => [
                        'specimenId' => $item->specimen_barcode,
                        'specimenType' => strtoupper($item->test->specimen_type),
                        'collectionMethod' => 'VENIPUNCTURE',
                    ],
                ];
            })->toArray(),
            'billingInfo' => [
                'billTo' => 'CLIENT',
                'accountNumber' => config('lab-partners.labcorp.account_number'),
            ],
        ];
    }

    /**
     * Map gender to LabCorp format
     *
     * @param string|null $gender
     * @return string
     */
    protected function mapGender(?string $gender): string
    {
        return match (strtolower($gender ?? '')) {
            'male', 'm' => 'M',
            'female', 'f' => 'F',
            default => 'U',
        };
    }

    /**
     * Map test code to LabCorp's codes
     *
     * @param string $testCode
     * @return string
     */
    protected function mapTestCode(string $testCode): string
    {
        $mapping = $this->labPartner->field_mapping['test_codes'] ?? [];
        return $mapping[$testCode] ?? $testCode;
    }

    /**
     * Format address for LabCorp API
     *
     * @param array|null $address
     * @return array
     */
    protected function formatAddress(?array $address): array
    {
        if (!$address) {
            return [
                'street1' => '',
                'city' => '',
                'state' => '',
                'zipCode' => '',
                'country' => 'US',
            ];
        }

        return [
            'street1' => $address['line1'] ?? '',
            'street2' => $address['line2'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'zipCode' => $address['postcode'] ?? '',
            'country' => $address['country'] ?? 'US',
        ];
    }

    /**
     * Get order status from LabCorp
     *
     * @param string $labOrderId
     * @return array
     * @throws \Exception
     */
    public function getOrderStatus(string $labOrderId): array
    {
        try {
            $response = $this->client->get("/api/v2/orders/{$labOrderId}");

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'status' => $data['status'] ?? 'processing',
                    'result_available' => $data['resultAvailable'] ?? false,
                    'updated_at' => $data['lastUpdated'] ?? null,
                ];
            }

            throw new \Exception('Failed to get order status: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('LabCorp status check failed', [
                'lab_order_id' => $labOrderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse results from LabCorp format
     *
     * @param mixed $rawData
     * @return array
     */
    public function parseResults($rawData): array
    {
        $data = is_array($rawData) ? $rawData : json_decode($rawData, true);

        if (!isset($data['testResults'])) {
            throw new \Exception('Invalid LabCorp result format');
        }

        return [
            'tests' => collect($data['testResults'])->map(function ($result) {
                return [
                    'test_code' => $result['testCode'] ?? '',
                    'test_name' => $result['testName'] ?? '',
                    'value' => $result['resultValue'] ?? '',
                    'unit' => $result['unit'] ?? '',
                    'reference_range' => $this->formatReferenceRange($result),
                    'flag' => $this->normalizeFlag($result['flag'] ?? null),
                    'status' => $result['resultStatus'] ?? 'F',
                    'comments' => $result['comments'] ?? null,
                ];
            })->toArray(),
            'result_date' => $data['reportDate'] ?? now()->toISOString(),
            'performing_lab' => $data['performingLabId'] ?? $this->labPartner->name,
            'report_id' => $data['reportId'] ?? null,
        ];
    }

    /**
     * Format reference range
     *
     * @param array $result
     * @return string
     */
    protected function formatReferenceRange(array $result): string
    {
        $low = $result['referenceLow'] ?? null;
        $high = $result['referenceHigh'] ?? null;

        if ($low && $high) {
            return "{$low} - {$high}";
        }

        return $result['referenceRange'] ?? '';
    }

    /**
     * Normalize abnormal flags
     *
     * @param string|null $flag
     * @return string|null
     */
    protected function normalizeFlag(?string $flag): ?string
    {
        if (!$flag) {
            return null;
        }

        return match (strtoupper($flag)) {
            'HIGH', 'H', '>' => 'H',
            'LOW', 'L', '<' => 'L',
            'CRITICAL HIGH', 'HH', '>>' => 'HH',
            'CRITICAL LOW', 'LL', '<<' => 'LL',
            'ABNORMAL', 'A' => 'A',
            default => $flag,
        };
    }

    /**
     * Validate connection to LabCorp API
     *
     * @return bool
     */
    public function validateConnection(): bool
    {
        try {
            $response = $this->client->get('/api/v2/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('LabCorp connection validation failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get supported tests from LabCorp
     *
     * @return array
     */
    public function getSupportedTests(): array
    {
        try {
            $response = $this->client->get('/api/v2/tests/catalog');

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['tests'] ?? [])
                    ->pluck('testCode')
                    ->toArray();
            }

            return $this->labPartner->supported_tests ?? [];
        } catch (\Exception $e) {
            Log::warning('Failed to fetch LabCorp test catalog', [
                'error' => $e->getMessage(),
            ]);

            return $this->labPartner->supported_tests ?? [];
        }
    }
}