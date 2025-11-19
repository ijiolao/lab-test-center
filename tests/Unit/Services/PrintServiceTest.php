<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Services\PrintService;
use App\Services\PrinterConfigurationException;
use Tests\TestCase;

class PrintServiceTest extends TestCase
{
    public function test_disabled_printing_refuses_to_print(): void
    {
        config([
            'printing.enabled' => false,
            'printing.printer_ip' => null,
        ]);

        $service = new PrintService();

        $this->assertFalse($service->isEnabled());

        $this->expectException(PrinterConfigurationException::class);
        $service->printSpecimenLabel(new Order());
    }

    public function test_missing_printer_ip_is_reported_immediately(): void
    {
        $this->expectException(PrinterConfigurationException::class);

        config([
            'printing.enabled' => true,
            'printing.printer_ip' => null,
        ]);

        new PrintService();
    }
}
