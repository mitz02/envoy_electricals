<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPackageItem extends Model
{
    protected $fillable = [
        'solar_package_id', 'product_id', 'name', 'quantity', 'specification', 'unit_cost',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(SolarPackage::class, 'solar_package_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
