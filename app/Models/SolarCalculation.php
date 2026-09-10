<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolarCalculation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'appliances_json', 'total_connected_load', 'daily_consumption_kwh',
        'peak_load_kw', 'recommended_inverter', 'recommended_panels',
        'recommended_battery', 'recommended_package_id', 'estimated_price',
        'customer_name', 'customer_phone', 'customer_email', 'location',
        'lead_status', 'notes',
    ];

    protected $casts = [
        'appliances_json' => 'array',
        'total_connected_load' => 'float',
        'daily_consumption_kwh' => 'float',
        'peak_load_kw' => 'float',
        'recommended_panels' => 'int',
        'estimated_price' => 'float',
    ];

    protected $attributes = [
        'lead_status' => 'new',
    ];

    public function recommendedPackage(): BelongsTo
    {
        return $this->belongsTo(SolarPackage::class, 'recommended_package_id');
    }
}