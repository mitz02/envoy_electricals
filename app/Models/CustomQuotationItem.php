<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomQuotationItem extends Model
{
    protected $fillable = [
        'custom_quotation_id',
        'item',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(CustomQuotation::class, 'custom_quotation_id');
    }

    public static function calculateTotal($quantity, $unitPrice): float
    {
        return round($quantity * $unitPrice, 2);
    }
}
