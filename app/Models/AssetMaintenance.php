<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    protected $fillable = ['asset_id', 'maintenance_date', 'type', 'description', 'cost'];

    protected $casts = [
        'maintenance_date' => 'date',
        'cost' => 'float',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}