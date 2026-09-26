<?php

namespace App\Models;

use App\Models\Traits\HasStoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasStoreScope, SoftDeletes;

    protected $fillable = [
        'ref_id', 'purchase_date', 'invoice_no', 'supplier_id', 'store_id',
        'subtotal', 'discount', 'total', 'amount_paid', 'balance', 'payment_method',
        'status', 'remarks', 'created_by', 'void_reason', 'voided_by', 'voided_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'voided_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
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
