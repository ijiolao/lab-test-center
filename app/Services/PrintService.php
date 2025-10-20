<?php

namespace App\Services;

use App\Models\Order;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\EscposImage;

class PrintService
{
    protected $printer;

    public function __construct()
    {
        // Connect to network thermal printer
        $connector = new NetworkPrintConnector(
            config('printing.printer_ip'),
            config('printing.printer_port', 9100)
        );
        
        $this->printer = new Printer($connector);
    }

    public function printSpecimenLabel(Order $order, $itemId = null)
    {
        try {
            $user = $order->user;
            
            // Print header
            $this->printer->setJustification(Printer::JUSTIFY_CENTER);
            $this->printer->setTextSize(2, 2);
            $this->printer->text(config('app.name') . "\n");
            $this->printer->setTextSize(1, 1);
            $this->printer->text("Specimen Label\n");
            $this->printer->feed();

            // Patient info
            $this->printer->setJustification(Printer::JUSTIFY_LEFT);
            $this->printer->text("Patient: {$user->first_name} {$user->last_name}\n");
            $this->printer->text("DOB: {$user->date_of_birth->format('d/m/Y')}\n");
            $this->printer->text("Order: {$order->order_number}\n");
            $this->printer->feed();

            // Tests
            if ($itemId) {
                $item = $order->items()->find($itemId);
                $this->printItem($item);
            } else {
                foreach ($order->items as $item) {
                    $this->printItem($item);
                    $this->printer->feed();
                }
            }

            // Collection info
            $this->printer->text("Collection Date: " . 
                               $order->collection_date->format('d/m/Y H:i') . "\n");
            $this->printer->feed(2);

            $this->printer->cut();
            $this->printer->close();

        } catch (\Exception $e) {
            \Log::error('Print failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function printItem($item)
    {
        $this->printer->text("Test: {$item->test_name}\n");
        $this->printer->text("Code: {$item->test_code}\n");
        
        // Print barcode
        $this->printer->setBarcodeHeight(50);
        $this->printer->barcode($item->specimen_barcode, Printer::BARCODE_CODE128);
        $this->printer->feed();
    }
}