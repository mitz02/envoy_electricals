<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolarPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'name', 'description', 'package_price', 'installation_cost',
        'estimated_load_capacity', 'inverter_capacity', 'warranty', 'is_featured',
        'availability', 'components_json', 'featured_image_media_id', 'is_visible_online',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_visible_online' => 'boolean',
        'components_json' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SolarPackageItem::class);
    }
}
