<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'user_id', 'customer_id', 'subtotal', 'discount', 'tax',
        'delivery_fee', 'total', 'status', 'customer_name', 'customer_phone',
        'customer_email', 'delivery_address', 'payment_reference',
        'payment_provider', 'fulfillment', 'linked_sale_id',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
