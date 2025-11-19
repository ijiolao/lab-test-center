<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class PrinterConfigurationException extends \RuntimeException {}

class PrinterConfigurationException extends \RuntimeException {}

class PrintService
{
    protected ?Printer $printer = null;
    protected bool $enabled = true;

    public function __construct()
    {
        $this->enabled = (bool) config('printing.enabled', true);

        if (!$this->enabled) {
            return;
        }

        $printerIp = config('printing.printer_ip');
        $printerPort = config('printing.printer_port', 9100);

        if (!$printerIp) {
            throw new PrinterConfigurationException('No printer IP configured. Set PRINTING_PRINTER_IP in the environment.');
        }

        // Connect to network thermal printer
        $connector = new NetworkPrintConnector($printerIp, $printerPort);

        $this->printer = new Printer($connector);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->printer !== null;
    }

    protected function ensurePrinterIsReady(): void
    {
        if (!$this->isEnabled()) {
            throw new PrinterConfigurationException('Printing is disabled or printer is not configured.');
        }
    }

    public function printSpecimenLabel(Order $order, $itemId = null)
    {
        $this->ensurePrinterIsReady();

        try {
            $printer = $this->printer;
            $user = $order->user;

            // Print header
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(2, 2);
            $printer->text(config('app.name') . "\n");
            $printer->setTextSize(1, 1);
            $printer->text("Specimen Label\n");
            $printer->feed();

            // Patient info
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Patient: {$user->first_name} {$user->last_name}\n");
            $printer->text("DOB: " . $user->date_of_birth?->format('d/m/Y') . "\n");
            $printer->text("Order: {$order->order_number}\n");
            $printer->feed();

            // Tests
            if ($itemId) {
                $item = $order->items()->find($itemId);
                if (!$item) {
                    throw new PrinterConfigurationException('Selected test was not found on this order.');
                }
                $this->printItem($item);
            } else {
                foreach ($order->items as $item) {
                    $this->printItem($item);
                    $printer->feed();
                }
            }

            // Collection info
            $collectionDate = $order->collection_date?->format('d/m/Y') ?? 'N/A';
            $collectionTime = $order->collection_time?->format('H:i') ?? '--';
            $printer->text("Collection: {$collectionDate} {$collectionTime}\n");
            $printer->feed(2);

            $printer->cut();
            $printer->close();

        } catch (\Exception $e) {
            \Log::error('Print failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function printItem(OrderItem $item)
    {
        $this->ensurePrinterIsReady();
        $printer = $this->printer;

        $printer->text("Test: {$item->test_name}\n");
        $printer->text("Code: {$item->test_code}\n");

        // Print barcode
        $printer->setBarcodeHeight(50);
        $printer->barcode($item->specimen_barcode, Printer::BARCODE_CODE128);
        $printer->feed();
    }
}