<?php

namespace App\Services\LabPartner;

use App\Models\LabPartner;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Lab Partner Manager
 * 
 * Factory class for creating and managing lab partner adapters
 */
class LabPartnerManager
{
    /**
     * Registered adapter classes
     *
     * @var array
     */
    protected $adapters = [];

    public function __construct()
    {
        $this->registerAdapters();
    }

    /**
     * Register all available lab partner adapters
     *
     * @return void
     */
    protected function registerAdapters(): void
    {
        $this->adapters = [
            'quest' => QuestDiagnosticsAdapter::class,
            'labcorp' => LabCorpAdapter::class,
            'hl7' => HL7Adapter::class,
        ];
    }

    /**
     * Get adapter instance for a lab partner
     *
     * @param LabPartner $labPartner
     * @return LabPartnerInterface
     * @throws \Exception
     */
    public function getAdapter(LabPartner $labPartner): LabPartnerInterface
    {
        $adapterClass = $this->adapters[$labPartner->code] ?? null;

        if (!$adapterClass) {
            throw new \Exception("No adapter found for lab partner: {$labPartner->code}");
        }

        if (!class_exists($adapterClass)) {
            throw new \Exception("Adapter class not found: {$adapterClass}");
        }

        return new $adapterClass($labPartner);
    }

    /**
     * Submit order to appropriate lab partner
     *
     * @param Order $order
     * @param LabPartner $labPartner
     * @return \App\Models\LabSubmission
     * @throws \Exception
     */
    public function submitOrder(Order $order, LabPartner $labPartner)
    {
        if (!$order->canBeSubmittedToLab()) {
            throw new \Exception('Order cannot be submitted in current status');
        }

        if (!$labPartner->is_active) {
            throw new \Exception('Lab partner is not active');
        }

        $adapter = $this->getAdapter($labPartner);

        Log::info('Submitting order to lab partner', [
            'order_id' => $order->id,
            'lab_partner' => $labPartner->name,
        ]);

        return $adapter->submitOrder($order);
    }

    /**
     * Find best lab partner for an order
     *
     * @param Order $order
     * @return LabPartner|null
     */
    public function findBestLabPartner(Order $order): ?LabPartner
    {
        $testCodes = $order->items->pluck('test_code')->toArray();

        // Get active lab partners ordered by priority
        $partners = LabPartner::active()
            ->byPriority()
            ->get();

        foreach ($partners as $partner) {
            // Check if partner supports all tests in the order
            $supportsAll = true;
            foreach ($testCodes as $testCode) {
                if (!$partner->supportsTest($testCode)) {
                    $supportsAll = false;
                    break;
                }
            }

            if ($supportsAll && $partner->isAvailable()) {
                return $partner;
            }
        }

        return null;
    }

    /**
     * Test connection to all lab partners
     *
     * @return array Results of connection tests
     */
    public function testAllConnections(): array
    {
        $results = [];

        $partners = LabPartner::all();

        foreach ($partners as $partner) {
            try {
                $adapter = $this->getAdapter($partner);
                $connected = $adapter->validateConnection();

                $results[$partner->code] = [
                    'name' => $partner->name,
                    'connected' => $connected,
                    'error' => null,
                ];
            } catch (\Exception $e) {
                $results[$partner->code] = [
                    'name' => $partner->name,
                    'connected' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Register a custom adapter
     *
     * @param string $code
     * @param string $adapterClass
     * @return void
     */
    public function registerAdapter(string $code, string $adapterClass): void
    {
        $this->adapters[$code] = $adapterClass;
    }

    /**
     * Get all registered adapter codes
     *
     * @return array
     */
    public function getRegisteredAdapters(): array
    {
        return array_keys($this->adapters);
    }
}