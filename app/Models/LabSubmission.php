<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LabSubmission extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'lab_partner_id',
        'lab_order_id',
        'status',
        'request_payload',
        'response_payload',
        'submitted_at',
        'acknowledged_at',
        'error_message',
        'retry_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'submitted_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'lab_order_id', 'error_message'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    /**
     * Get the order that owns this submission
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the lab partner
     */
    public function labPartner()
    {
        return $this->belongsTo(LabPartner::class);
    }

    /**
     * Get the result associated with this submission
     */
    public function result()
    {
        return $this->hasOne(Result::class);
    }

    // Scopes

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending submissions
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'submitted']);
    }

    /**
     * Scope to get failed submissions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get successful submissions
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['submitted', 'acknowledged', 'completed']);
    }

    /**
     * Scope to get submissions awaiting retry
     */
    public function scopeAwaitingRetry($query)
    {
        $maxRetries = config('lab-partners.max_retries', 3);
        
        return $query->where('status', 'failed')
                    ->where('retry_count', '<', $maxRetries)
                    ->where('updated_at', '<=', now()->subMinutes(5));
    }

    /**
     * Scope to get submissions by lab partner
     */
    public function scopeForLabPartner($query, $labPartnerId)
    {
        return $query->where('lab_partner_id', $labPartnerId);
    }

    /**
     * Scope to get recent submissions
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors & Mutators

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'submitted', 'acknowledged' => 'blue',
            'processing' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Check if submission was successful
     */
    public function getIsSuccessfulAttribute(): bool
    {
        return in_array($this->status, ['submitted', 'acknowledged', 'completed']);
    }

    /**
     * Check if submission has failed
     */
    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if submission can be retried
     */
    public function getCanRetryAttribute(): bool
    {
        $maxRetries = config('lab-partners.max_retries', 3);
        return $this->is_failed && $this->retry_count < $maxRetries;
    }

    /**
     * Get time elapsed since submission
     */
    public function getTimeElapsedAttribute(): ?string
    {
        if (!$this->submitted_at) {
            return null;
        }

        return $this->submitted_at->diffForHumans();
    }

    /**
     * Get turnaround time in hours
     */
    public function getTurnaroundTimeAttribute(): ?float
    {
        if (!$this->submitted_at || !$this->result) {
            return null;
        }

        return round(
            $this->submitted_at->diffInHours($this->result->result_date),
            1
        );
    }

    // Methods

    /**
     * Mark submission as submitted
     */
    public function markAsSubmitted(string $labOrderId, ?array $responsePayload = null): bool
    {
        if (!in_array($this->status, ['pending', 'failed'])) {
            throw new \Exception("Cannot mark as submitted from status: {$this->status}");
        }

        return $this->update([
            'status' => 'submitted',
            'lab_order_id' => $labOrderId,
            'response_payload' => $responsePayload,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Mark submission as acknowledged with state validation
     */
    public function markAsAcknowledged(?array $responsePayload = null): bool
    {
        if (!in_array($this->status, ['submitted', 'processing'])) {
            throw new \Exception("Cannot mark as acknowledged from status: {$this->status}");
        }

        return $this->update([
            'status' => 'acknowledged',
            'response_payload' => array_merge($this->response_payload ?? [], $responsePayload ?? []),
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Mark submission as processing with state validation
     */
    public function markAsProcessing(?array $responsePayload = null): bool
    {
        if (!in_array($this->status, ['submitted', 'acknowledged'])) {
            throw new \Exception("Cannot mark as processing from status: {$this->status}");
        }

        return $this->update([
            'status' => 'processing',
            'response_payload' => array_merge($this->response_payload ?? [], $responsePayload ?? []),
        ]);
    }

    /**
     * Mark submission as completed with state validation
     */
    public function markAsCompleted(): bool
    {
        if (!in_array($this->status, ['submitted', 'acknowledged', 'processing'])) {
            throw new \Exception("Cannot mark as completed from status: {$this->status}");
        }

        return $this->update([
            'status' => 'completed',
        ]);
    }

    /**
     * Mark submission as failed
     */
    public function markAsFailed(string $errorMessage, ?array $responsePayload = null): bool
    {
        // Can fail from any status except completed
        if ($this->status === 'completed') {
            throw new \Exception("Cannot mark completed submission as failed");
        }

        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'response_payload' => $responsePayload,
        ]);
    }

    /**
     * Retry submission
     */
    public function retry(): void
    {
        $this->increment('retry_count');
        
        try {
            $manager = app(\App\Services\LabPartner\LabPartnerManager::class);
            $manager->submitOrder($this->order, $this->labPartner);
        } catch (\Exception $e) {
            $this->markAsFailed($e->getMessage());
            
            \Log::error('Lab submission retry failed', [
                'submission_id' => $this->id,
                'order_id' => $this->order_id,
                'retry_count' => $this->retry_count,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get formatted request payload for display
     */
    public function getFormattedRequestPayload(): string
    {
        if (!$this->request_payload) {
            return 'N/A';
        }

        return json_encode($this->request_payload, JSON_PRETTY_PRINT);
    }

    /**
     * Get formatted response payload for display
     */
    public function getFormattedResponsePayload(): string
    {
        if (!$this->response_payload) {
            return 'N/A';
        }

        return json_encode($this->response_payload, JSON_PRETTY_PRINT);
    }

    /**
     * Check if submission is still processing
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['pending', 'submitted', 'acknowledged', 'processing']);
    }

    /**
     * Get processing duration
     */
    public function getProcessingDuration(): ?string
    {
        if (!$this->submitted_at) {
            return null;
        }

        $endTime = $this->result 
            ? $this->result->result_date 
            : now();

        $hours = $this->submitted_at->diffInHours($endTime);
        
        if ($hours < 1) {
            return $this->submitted_at->diffInMinutes($endTime) . ' minutes';
        } elseif ($hours < 24) {
            return $hours . ' hours';
        } else {
            return $this->submitted_at->diffInDays($endTime) . ' days';
        }
    }

     /**
     * Validate state transition
     */
    protected function canTransitionTo(string $newStatus): bool
    {
        $transitions = [
            'pending' => ['submitted', 'failed'],
            'submitted' => ['acknowledged', 'processing', 'completed', 'failed'],
            'acknowledged' => ['processing', 'completed', 'failed'],
            'processing' => ['completed', 'failed'],
            'completed' => [], // Terminal state
            'failed' => ['pending'], // Can retry
        ];

        return in_array($newStatus, $transitions[$this->status] ?? []);
    }
}