<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'ref_id', 'payment_date', 'amount', 'payment_method', 'document_type',
        'document_id', 'customer_id', 'supplier_id', 'type', 'reference',
        'status', 'gateway', 'gateway_reference', 'paid_at',
        'remarks', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_REFUNDED = 'refunded';

    public const GATEWAY_LOCAL = 'local';
    public const GATEWAY_PAYSTACK = 'paystack';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
