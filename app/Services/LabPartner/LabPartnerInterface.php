<?php

namespace App\Services\LabPartner;

use App\Models\Order;
use App\Models\LabSubmission;

/**
 * Interface for all lab partner integrations
 * 
 * All lab partner adapters must implement this interface
 * to ensure consistent integration patterns
 */
interface LabPartnerInterface
{
    /**
     * Submit an order to the lab partner
     *
     * @param Order $order
     * @return LabSubmission
     * @throws \Exception
     */
    public function submitOrder(Order $order): LabSubmission;

    /**
     * Check the status of an order at the lab partner
     *
     * @param string $labOrderId External lab's order ID
     * @return array Status information
     * @throws \Exception
     */
    public function getOrderStatus(string $labOrderId): array;

    /**
     * Parse and normalize results from lab partner
     *
     * @param mixed $rawData Raw result data from lab
     * @return array Normalized result data structure
     * @throws \Exception
     */
    public function parseResults($rawData): array;

    /**
     * Validate connection to lab partner
     *
     * @return bool True if connection is successful
     */
    public function validateConnection(): bool;

    /**
     * Get list of tests supported by this lab partner
     *
     * @return array Array of test codes
     */
    public function getSupportedTests(): array;
}