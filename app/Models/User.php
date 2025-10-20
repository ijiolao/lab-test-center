<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, Billable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'date_of_birth',
        'phone',
        'gender',
        'address',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'address' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Activity log configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['email', 'role', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    /**
     * Get all orders for the user
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get orders collected by this user (technician)
     */
    public function collectedOrders()
    {
        return $this->hasMany(Order::class, 'collected_by');
    }

    /**
     * Get results reviewed by this user
     */
    public function reviewedResults()
    {
        return $this->hasMany(Result::class, 'reviewed_by');
    }

    // Scopes

    /**
     * Scope to get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get users by role
     */
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get patients
     */
    public function scopePatients($query)
    {
        return $query->where('role', 'patient');
    }

    /**
     * Scope to get admin users
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['admin', 'technician', 'reviewer']);
    }

    // Accessors & Mutators

    /**
     * Get the user's full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute(): ?string
    {
        if (!$this->address) {
            return null;
        }

        $parts = [
            $this->address['line1'] ?? '',
            $this->address['line2'] ?? '',
            $this->address['city'] ?? '',
            $this->address['postcode'] ?? '',
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Get the user's age
     */
    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    // Methods

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'technician', 'reviewer']);
    }

    /**
     * Check if user is a patient
     */
    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    /**
     * Check if user can collect specimens
     */
    public function canCollectSpecimens(): bool
    {
        return in_array($this->role, ['admin', 'technician']);
    }

    /**
     * Check if user can review results
     */
    public function canReviewResults(): bool
    {
        return in_array($this->role, ['admin', 'reviewer']);
    }

    /**
     * Get pending results count for patient
     */
    public function getPendingResultsCount(): int
    {
        return $this->orders()
            ->whereIn('status', ['processing', 'completed'])
            ->whereDoesntHave('result', function ($query) {
                $query->whereNotNull('patient_viewed_at');
            })
            ->count();
    }
}