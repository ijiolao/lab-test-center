<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabPartner extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'connection_type',
        'api_endpoint',
        'api_key',
        'api_secret',
        'auth_type',
        'credentials',
        'supported_tests',
        'field_mapping',
        'priority',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'supported_tests' => 'array',
            'field_mapping' => 'array',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key',
        'api_secret',
        'credentials',
    ];

    // Relationships

    /**
     * Get all lab submissions for this partner
     */
    public function labSubmissions()
    {
        return $this->hasMany(LabSubmission::class);
    }

    /**
     * Get all results from this partner
     */
    public function results()
    {
        return $this->hasManyThrough(Result::class, LabSubmission::class);
    }

    // Scopes

    /**
     * Scope to get only active partners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Scope to filter by connection type
     */
    public function scopeConnectionType($query, $type)
    {
        return $query->where('connection_type', $type);
    }

    /**
     * Scope to filter partners that support a specific test
     */
    public function scopeSupportsTest($query, $testCode)
    {
        return $query->where(function ($q) use ($testCode) {
            $q->whereJsonContains('supported_tests', $testCode)
              ->orWhereNull('supported_tests'); // NULL means supports all tests
        });
    }

    // Accessors & Mutators

    /**
     * Get decrypted API key
     */
    public function getDecryptedApiKeyAttribute(): ?string
    {
        if (!$this->api_key) {
            return null;
        }

        try {
            return decrypt($this->api_key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get decrypted API secret
     */
    public function getDecryptedApiSecretAttribute(): ?string
    {
        if (!$this->api_secret) {
            return null;
        }

        try {
            return decrypt($this->api_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted API key
     */
    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = $value ? encrypt($value) : null;
    }

    /**
     * Set encrypted API secret
     */
    public function setApiSecretAttribute($value): void
    {
        $this->attributes['api_secret'] = $value ? encrypt($value) : null;
    }

    /**
     * Get connection status
     */
    public function getConnectionStatusAttribute(): string
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    /**
     * Get connection type display name
     */
    public function getConnectionTypeNameAttribute(): string
    {
        return match($this->connection_type) {
            'api' => 'REST API',
            'hl7' => 'HL7/MLLP',
            'manual' => 'Manual',
            default => ucfirst($this->connection_type),
        };
    }

    // Methods

    /**
     * Check if partner supports a specific test
     */
    public function supportsTest(string $testCode): bool
    {
        // If supported_tests is null, it supports all tests
        if (is_null($this->supported_tests)) {
            return true;
        }

        return in_array($testCode, $this->supported_tests);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        $total = $this->labSubmissions()->count();
        
        if ($total === 0) {
            return 0;
        }

        $successful = $this->labSubmissions()
                          ->whereIn('status', ['submitted', 'acknowledged', 'completed'])
                          ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get average turnaround time in hours
     */
    public function getAverageTurnaroundTime(): ?float
    {
        $submissions = $this->labSubmissions()
            ->whereNotNull('submitted_at')
            ->whereHas('order.result')
            ->with('order.result')
            ->get();

        if ($submissions->isEmpty()) {
            return null;
        }

        $totalHours = 0;
        $count = 0;

        foreach ($submissions as $submission) {
            if ($submission->order->result) {
                $hours = $submission->submitted_at->diffInHours(
                    $submission->order->result->result_date
                );
                $totalHours += $hours;
                $count++;
            }
        }

        return $count > 0 ? round($totalHours / $count, 1) : null;
    }

    /**
     * Get total submissions count
     */
    public function getTotalSubmissions(): int
    {
        return $this->labSubmissions()->count();
    }

    /**
     * Get pending submissions count
     */
    public function getPendingSubmissions(): int
    {
        return $this->labSubmissions()
                    ->whereIn('status', ['pending', 'submitted'])
                    ->count();
    }

    /**
     * Get failed submissions count
     */
    public function getFailedSubmissions(): int
    {
        return $this->labSubmissions()
                    ->where('status', 'failed')
                    ->count();
    }

    /**
     * Test connection to lab partner
     */
    public function testConnection(): bool
    {
        try {
            $manager = app(\App\Services\LabPartner\LabPartnerManager::class);
            $adapter = $manager->getAdapter($this);
            return $adapter->validateConnection();
        } catch (\Exception $e) {
            \Log::error("Lab partner connection test failed for {$this->name}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get mapped field value
     */
    public function getMappedField(string $ourField): ?string
    {
        if (!$this->field_mapping) {
            return $ourField; // Use same field name if no mapping
        }

        return $this->field_mapping[$ourField] ?? $ourField;
    }

    /**
     * Check if partner is available for new submissions
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->getFailedSubmissions() < 10;
    }
}