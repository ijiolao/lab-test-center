<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
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
        'loinc_code',
        'description',
        'price',
        'specimen_type',
        'turnaround_days',
        'fasting_required',
        'preparation_instructions',
        'normal_ranges',
        'category',
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
            'price' => 'decimal:2',
            'turnaround_days' => 'integer',
            'fasting_required' => 'boolean',
            'normal_ranges' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    /**
     * Get all order items for this test
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all orders that include this test
     */
    public function orders()
    {
        return $this->hasManyThrough(Order::class, OrderItem::class);
    }

    // Scopes

    /**
     * Scope to get only active tests
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get tests requiring fasting
     */
    public function scopeRequiresFasting($query)
    {
        return $query->where('fasting_required', true);
    }

    /**
     * Scope to search tests by name or code
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('loinc_code', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to order by popularity (most ordered)
     */
    public function scopePopular($query)
    {
        return $query->withCount('orderItems')
                    ->orderBy('order_items_count', 'desc');
    }

    // Accessors & Mutators

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '£' . number_format($this->price, 2);
    }

    /**
     * Get expected completion date
     */
    public function getExpectedCompletionAttribute(): string
    {
        $days = $this->turnaround_days;
        return $days === 1 ? '1 day' : "{$days} days";
    }

    /**
     * Check if test requires special preparation
     */
    public function getRequiresPreparationAttribute(): bool
    {
        return $this->fasting_required || !empty($this->preparation_instructions);
    }

    // Methods

    /**
     * Get normal range for specific demographic
     */
    public function getNormalRange($age = null, $gender = null): ?array
    {
        if (!$this->normal_ranges) {
            return null;
        }

        // If no demographic-specific ranges, return default
        if (isset($this->normal_ranges['default'])) {
            return $this->normal_ranges['default'];
        }

        // Try to find matching demographic range
        foreach ($this->normal_ranges as $range) {
            if ($this->matchesDemographic($range, $age, $gender)) {
                return $range;
            }
        }

        return null;
    }

    /**
     * Check if a range matches demographic criteria
     */
    protected function matchesDemographic(array $range, $age, $gender): bool
    {
        if (isset($range['gender']) && $range['gender'] !== $gender) {
            return false;
        }

        if (isset($range['min_age']) && $age < $range['min_age']) {
            return false;
        }

        if (isset($range['max_age']) && $age > $range['max_age']) {
            return false;
        }

        return true;
    }

    /**
     * Get all unique categories
     */
    public static function getCategories(): array
    {
        return static::active()
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Get specimen types
     */
    public static function getSpecimenTypes(): array
    {
        return [
            'blood' => 'Blood',
            'urine' => 'Urine',
            'saliva' => 'Saliva',
            'stool' => 'Stool',
            'swab' => 'Swab',
            'tissue' => 'Tissue',
            'other' => 'Other',
        ];
    }
}