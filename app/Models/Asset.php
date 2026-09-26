<?php

namespace App\Models;

use App\Models\Traits\HasStoreScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasStoreScope, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DISPOSED = 'disposed';

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DISPOSED];

    protected $fillable = [
        'ref_id', 'name', 'category', 'purchase_date', 'purchase_cost',
        'serial_number', 'location', 'condition', 'current_value', 'status', 'notes',
        'store_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'float',
        'current_value' => 'float',
    ];

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
