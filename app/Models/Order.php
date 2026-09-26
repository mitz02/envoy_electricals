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

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'delivery_fee' => 'float',
        'total' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Payments recorded against this order in the payments ledger.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'document_id', 'id')
            ->where('document_type', 'order');
    }

    /**
     * Amount successfully paid so far, derived from the payments ledger.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->sum('amount');
    }

    /**
     * Outstanding amount still owed on the order.
     */
    public function getBalanceAttribute(): float
    {
        return round((float) $this->total - $this->total_paid, 2);
    }
}
