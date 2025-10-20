<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'test_id',
        'test_name',
        'test_code',
        'price',
        'specimen_barcode',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    // ===================================
    // RELATIONSHIPS
    // ===================================

    /**
     * Get the order that owns the item
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the test associated with this item
     */
    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    // ===================================
    // ACCESSORS & MUTATORS
    // ===================================

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '£' . number_format($this->price, 2);
    }

    /**
     * Check if specimen has been collected
     */
    public function getIsCollectedAttribute(): bool
    {
        return $this->order->is_collected;
    }

    /**
     * Get barcode type for display
     */
    public function getBarcodeTypeAttribute(): string
    {
        return 'CODE128'; // Standard barcode type
    }

    // ===================================
    // METHODS
    // ===================================

    /**
     * Generate unique specimen barcode
     * Format: ORD-2025-001234-CBC-A1B2C3
     */
    public static function generateBarcode(string $orderNumber, string $testCode): string
    {
        return strtoupper(
            $orderNumber . '-' . 
            $testCode . '-' . 
            substr(md5(uniqid(mt_rand(), true)), 0, 6)
        );
    }

    /**
     * Check if barcode is valid format
     */
    public function isValidBarcode(): bool
    {
        return !empty($this->specimen_barcode) && 
               strlen($this->specimen_barcode) >= 10;
    }

    /**
     * Get the specimen collection status
     */
    public function getCollectionStatus(): string
    {
        if (!$this->order->collected_at) {
            return 'pending';
        }

        if ($this->order->status === 'sent_to_lab') {
            return 'sent_to_lab';
        }

        return 'collected';
    }

    /**
     * Get specimen type from test
     */
    public function getSpecimenType(): string
    {
        return $this->test->specimen_type ?? 'unknown';
    }

    /**
     * Check if this is a fasting test
     */
    public function requiresFasting(): bool
    {
        return $this->test->fasting_required ?? false;
    }
}