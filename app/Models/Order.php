<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax',
        'total',
        'payment_status',
        'payment_method',
        'payment_intent_id',
        'collection_date',
        'collection_time',
        'collection_location',
        'special_instructions',
        'collected_at',
        'collected_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'collection_time' => 'datetime',
            'collected_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'payment_status', 'collected_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    /**
     * Get the user that owns the order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all order items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all lab submissions for this order
     */
    public function labSubmissions()
    {
        return $this->hasMany(LabSubmission::class);
    }

    /**
     * Get the result for this order
     */
    public function result()
    {
        return $this->hasOne(Result::class);
    }

    /**
     * Get the user who collected the specimen
     */
    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // Scopes

    /**
     * Scope to get orders for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('collection_date', today());
    }

    /**
     * Scope to get pending orders
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['paid', 'scheduled']);
    }

    /**
     * Scope to get orders collected today
     */
    public function scopeCollectedToday($query)
    {
        return $query->whereDate('collected_at', today());
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by payment status
     */
    public function scopePaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope to search orders
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('order_number', 'like', "%{$search}%")
              ->orWhereHas('user', function ($q) use ($search) {
                  $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Scope for orders awaiting lab submission
     */
    public function scopeAwaitingLabSubmission($query)
    {
        return $query->where('status', 'collected')
                    ->whereDoesntHave('labSubmissions', function ($q) {
                        $q->where('status', 'submitted');
                    });
    }

    /**
     * Scope for orders awaiting results
     */
    public function scopeAwaitingResults($query)
    {
        return $query->whereIn('status', ['sent_to_lab', 'processing'])
                    ->doesntHave('result');
    }

    // Accessors & Mutators

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute(): string
    {
        return '£' . number_format($this->total, 2);
    }

    /**
     * Get formatted subtotal
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '£' . number_format($this->subtotal, 2);
    }

    /**
     * Get formatted tax
     */
    public function getFormattedTaxAttribute(): string
    {
        return '£' . number_format($this->tax, 2);
    }

    /**
     * Check if order can be printed
     */
    public function getCanBePrintedAttribute(): bool
    {
        return in_array($this->status, ['paid', 'scheduled', 'collected']);
    }

    /**
     * Check if order is paid
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order is collected
     */
    public function getIsCollectedAttribute(): bool
    {
        return !is_null($this->collected_at);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending_payment' => 'gray',
            'paid', 'scheduled' => 'blue',
            'collected' => 'green',
            'sent_to_lab', 'processing' => 'yellow',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get formatted collection datetime
     */
    public function getCollectionDateTimeAttribute(): ?string
    {
        if (!$this->collection_date) {
            return null;
        }

        $date = $this->collection_date->format('d/m/Y');
        $time = $this->collection_time ? $this->collection_time->format('H:i') : '';
        
        return trim("{$date} {$time}");
    }

    // Methods

    /**
     * Generate unique order number
     */
    public function generateOrderNumber(): string
    {
        $year = date('Y');
        $lastOrder = static::whereYear('created_at', $year)
                          ->latest('id')
                          ->first();
        
        $number = $lastOrder ? intval(substr($lastOrder->order_number, -6)) + 1 : 1;
        
        return 'ORD-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Mark order as collected
     */
    public function markAsCollected(?int $userId = null): bool
    {
        $this->update([
            'status' => 'collected',
            'collected_at' => now(),
            'collected_by' => $userId ?? auth()->id(),
        ]);

        event(new \App\Events\OrderStatusChanged($this));

        return true;
    }

    /**
     * Check if order can be submitted to lab
     */
    public function canBeSubmittedToLab(): bool
    {
        return $this->status === 'collected';
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending_payment', 'paid', 'scheduled']);
    }

    /**
     * Cancel the order
     */
    public function cancel(?string $reason = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
        ]);

        if ($reason) {
            activity()
                ->performedOn($this)
                ->withProperties(['reason' => $reason])
                ->log('Order cancelled');
        }

        // Trigger refund if paid
        if ($this->is_paid && $this->payment_intent_id) {
            // Handle refund logic here
        }

        return true;
    }

    /**
     * Get order progress percentage
     */
    public function getProgressPercentage(): int
    {
        return match($this->status) {
            'pending_payment' => 10,
            'paid', 'scheduled' => 25,
            'collected' => 40,
            'sent_to_lab' => 60,
            'processing' => 80,
            'completed' => 100,
            'cancelled' => 0,
            default => 0,
        };
    }

    /**
     * Get the latest lab submission
     */
    public function getLatestLabSubmission(): ?LabSubmission
    {
        return $this->labSubmissions()->latest()->first();
    }

    /**
     * Check if order has any failed submissions
     */
    public function hasFailedSubmissions(): bool
    {
        return $this->labSubmissions()
                    ->where('status', 'failed')
                    ->exists();
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayName(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Check if results are available
     */
    public function hasResults(): bool
    {
        return $this->result()->exists();
    }

    /**
     * Check if patient has viewed results
     */
    public function hasPatientViewedResults(): bool
    {
        return $this->result && $this->result->patient_viewed_at !== null;
    }
}