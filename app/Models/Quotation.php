<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'customer_id', 'customer_name', 'customer_phone', 'customer_email',
        'location', 'appliances_json', 'recommended_system', 'solar_package_id',
        'estimated_price', 'additional_logistics', 'status', 'notes',
    ];

    protected $casts = [
        'estimated_price' => 'float',
        'additional_logistics' => 'float',
        'appliances_json' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function solarPackage(): BelongsTo
    {
        return $this->belongsTo(SolarPackage::class);
    }
}
