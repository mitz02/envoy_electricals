<?php

namespace App\Models;

use App\Models\Traits\HasStoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasStoreScope, SoftDeletes;

    protected $fillable = [
        'ref_id', 'invoice_no', 'sale_date', 'customer_id', 'salesperson_id',
        'store_id', 'subtotal', 'discount', 'tax_rate', 'tax', 'total', 'amount_paid',
        'balance', 'payment_method', 'status', 'remarks', 'created_by',
        'void_reason', 'voided_by', 'voided_at',
    ];

    protected $casts = ['sale_date' => 'date', 'voided_at' => 'datetime'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
