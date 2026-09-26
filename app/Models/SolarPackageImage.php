<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPackageImage extends Model
{
    protected $fillable = [
        'solar_package_id',
        'media_id',
        'sort_order',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function solarPackage(): BelongsTo
    {
        return $this->belongsTo(SolarPackage::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
