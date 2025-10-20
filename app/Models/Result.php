<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Result extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'lab_submission_id',
        'raw_data',
        'parsed_data',
        'pdf_path',
        'result_date',
        'has_critical_values',
        'is_reviewed',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'patient_notified_at',
        'patient_viewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'parsed_data' => 'array',
            'result_date' => 'datetime',
            'has_critical_values' => 'boolean',
            'is_reviewed' => 'boolean',
            'reviewed_at' => 'datetime',
            'patient_notified_at' => 'datetime',
            'patient_viewed_at' => 'datetime',
        ];
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['is_reviewed', 'reviewed_at', 'patient_viewed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    /**
     * Get the order that owns the result
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the lab submission
     */
    public function labSubmission()
    {
        return $this->belongsTo(LabSubmission::class);
    }

    /**
     * Get the user who reviewed the result
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes

    /**
     * Scope to get results with critical values
     */
    public function scopeCritical($query)
    {
        return $query->where('has_critical_values', true);
    }

    /**
     * Scope to get reviewed results
     */
    public function scopeReviewed($query)
    {
        return $query->where('is_reviewed', true);
    }

    /**
     * Scope to get unreviewed results
     */
    public function scopeUnreviewed($query)
    {
        return $query->where('is_reviewed', false);
    }

    /**
     * Scope to get results awaiting patient view
     */
    public function scopeAwaitingPatientView($query)
    {
        return $query->whereNull('patient_viewed_at')
                    ->whereNotNull('patient_notified_at');
    }

    /**
     * Scope to get new results (not yet notified)
     */
    public function scopeNew($query)
    {
        return $query->whereNull('patient_notified_at');
    }

    /**
     * Scope to get recent results
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('result_date', '>=', now()->subDays($days));
    }

    /**
     * Scope to get results by patient
     */
    public function scopeForPatient($query, $userId)
    {
        return $query->whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    // Accessors & Mutators

    /**
     * Get PDF URL
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        return Storage::url($this->pdf_path);
    }

    /**
     * Check if PDF exists
     */
    public function getHasPdfAttribute(): bool
    {
        return !empty($this->pdf_path) && Storage::exists($this->pdf_path);
    }

    /**
     * Check if patient has been notified
     */
    public function getIsNotifiedAttribute(): bool
    {
        return !is_null($this->patient_notified_at);
    }

    /**
     * Check if patient has viewed result
     */
    public function getIsViewedAttribute(): bool
    {
        return !is_null($this->patient_viewed_at);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->has_critical_values) {
            return 'red';
        }
        
        if (!$this->is_reviewed) {
            return 'yellow';
        }
        
        if (!$this->is_notified) {
            return 'blue';
        }
        
        if (!$this->is_viewed) {
            return 'green';
        }
        
        return 'gray';
    }

    /**
     * Get result status
     */
    public function getStatusAttribute(): string
    {
        if ($this->has_critical_values && !$this->is_reviewed) {
            return 'critical_review_required';
        }
        
        if (!$this->is_reviewed) {
            return 'awaiting_review';
        }
        
        if (!$this->is_notified) {
            return 'ready_to_notify';
        }
        
        if (!$this->is_viewed) {
            return 'awaiting_patient_view';
        }
        
        return 'completed';
    }

    /**
     * Get formatted result date
     */
    public function getFormattedResultDateAttribute(): string
    {
        return $this->result_date->format('d/m/Y H:i');
    }

    /**
     * Get time since result
     */
    public function getTimeSinceResultAttribute(): string
    {
        return $this->result_date->diffForHumans();
    }

    // Methods

    /**
     * Get all test results from parsed data
     */
    public function getTests(): array
    {
        return $this->parsed_data['tests'] ?? [];
    }

    /**
     * Get a specific test result by code
     */
    public function getTestByCode(string $code): ?array
    {
        $tests = $this->getTests();
        
        foreach ($tests as $test) {
            if ($test['test_code'] === $code) {
                return $test;
            }
        }
        
        return null;
    }

    /**
     * Get tests with abnormal flags
     */
    public function getAbnormalTests(): array
    {
        return array_filter($this->getTests(), function ($test) {
            return isset($test['flag']) && 
                   in_array($test['flag'], ['H', 'L', 'HH', 'LL', 'A']);
        });
    }

    /**
     * Get critical tests
     */
    public function getCriticalTests(): array
    {
        return array_filter($this->getTests(), function ($test) {
            return isset($test['flag']) && 
                   in_array($test['flag'], ['HH', 'LL', 'CRIT']);
        });
    }

    /**
     * Mark result as reviewed
     */
    public function markAsReviewed(?int $reviewerId = null, ?string $notes = null): bool
    {
        return $this->update([
            'is_reviewed' => true,
            'reviewed_by' => $reviewerId ?? auth()->id(),
            'reviewed_at' => now(),
            'reviewer_notes' => $notes,
        ]);
    }

    /**
     * Mark patient as notified
     */
    public function markAsNotified(): bool
    {
        return $this->update([
            'patient_notified_at' => now(),
        ]);
    }

    /**
     * Mark result as viewed by patient
     */
    public function markAsViewed(): bool
    {
        if ($this->is_viewed) {
            return true; // Already viewed
        }

        $updated = $this->update([
            'patient_viewed_at' => now(),
        ]);

        if ($updated) {
            activity()
                ->performedOn($this)
                ->causedBy($this->order->user)
                ->log('Patient viewed result');
        }

        return $updated;
    }

    /**
     * Check if result requires urgent attention
     */
    public function requiresUrgentAttention(): bool
    {
        return $this->has_critical_values && !$this->is_reviewed;
    }

    /**
     * Get performing lab name
     */
    public function getPerformingLab(): ?string
    {
        return $this->parsed_data['performing_lab'] ?? 
               $this->labSubmission?->labPartner->name ?? 
               null;
    }

    /**
     * Download PDF with validation
     */
    public function downloadPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!$this->pdf_path) {
            throw new \Exception('PDF path not set for this result');
        }

        if (!Storage::exists($this->pdf_path)) {
            \Log::error('PDF file not found on storage', [
                'result_id' => $this->id,
                'pdf_path' => $this->pdf_path,
            ]);
            throw new \Exception('PDF file not found. It may have been deleted or not generated properly.');
        }

        $filename = "result_{$this->order->order_number}.pdf";
        
        // Log the download
        activity()
            ->performedOn($this)
            ->log('Result PDF downloaded');
        
        return Storage::download($this->pdf_path, $filename);
    }

    /**
     * Delete PDF file from storage
     */
    public function deletePdf(): bool
    {
        if (!$this->pdf_path) {
            return false;
        }

        if (Storage::exists($this->pdf_path)) {
            Storage::delete($this->pdf_path);
        }

        return $this->update(['pdf_path' => null]);
    }

    /**
     * Regenerate PDF
     */
    public function regeneratePdf(): bool
    {
        try {
            $this->deletePdf();
            
            $resultService = app(\App\Services\ResultService::class);
            $resultService->generatePDF($this);
            
            \Log::info('PDF regenerated', [
                'result_id' => $this->id,
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to regenerate PDF', [
                'result_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        $tests = $this->getTests();
        $abnormal = $this->getAbnormalTests();
        $critical = $this->getCriticalTests();

        return [
            'total_tests' => count($tests),
            'abnormal_count' => count($abnormal),
            'critical_count' => count($critical),
            'normal_count' => count($tests) - count($abnormal),
            'has_concerns' => count($abnormal) > 0 || count($critical) > 0,
        ];
    }

    /**
     * Check if result can be shared with patient
     */
    public function canBeSharedWithPatient(): bool
    {
        // Results with critical values must be reviewed first
        if ($this->has_critical_values && !$this->is_reviewed) {
            return false;
        }

        // Must have PDF generated
        if (!$this->has_pdf) {
            return false;
        }

        return true;
    }

    /**
     * Get patient who owns this result
     */
    public function getPatient(): User
    {
        return $this->order->user;
    }

     /**
     * Validate PDF integrity
     */
    public function validatePdf(): array
    {
        if (!$this->pdf_path) {
            return [
                'valid' => false,
                'error' => 'No PDF path set',
            ];
        }

        if (!Storage::exists($this->pdf_path)) {
            return [
                'valid' => false,
                'error' => 'PDF file does not exist on storage',
            ];
        }

        $fileSize = Storage::size($this->pdf_path);
        
        if ($fileSize === 0) {
            return [
                'valid' => false,
                'error' => 'PDF file is empty (0 bytes)',
            ];
        }

        if ($fileSize < 1000) { // Less than 1KB is suspicious
            return [
                'valid' => false,
                'error' => 'PDF file is too small, may be corrupted',
            ];
        }

        return [
            'valid' => true,
            'size' => $fileSize,
            'size_formatted' => number_format($fileSize / 1024, 2) . ' KB',
        ];
    }
}