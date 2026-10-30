<?php

namespace App\Models;

use App\Models\Traits\HasStoreScope;
use App\Services\ReferenceGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Receipt extends Model
{
    use HasFactory, HasStoreScope, SoftDeletes;

    protected $fillable = [
        'ref_id',
        'token',
        'receipt_date',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'amount',
        'amount_in_words',
        'payment_for',
        'payment_method',
        'customer_signature',
        'envoy_signature',
        'signed_at',
        'signatory_title',
        'issued_by',
        'store_id',
        'status',
        'email_sent_at',
        'whatsapp_sent_at',
        'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
        'signed_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ref_id)) {
                $model->ref_id = ReferenceGenerator::generate('receipt');
            }
            if (empty($model->token)) {
                $model->token = Str::random(40);
            }
            if (empty($model->receipt_date)) {
                $model->receipt_date = now()->toDateString();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 2);
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/receipt/{$this->token}");
    }

    public function getSigningUrlAttribute(): string
    {
        return url("/receipt/{$this->token}/sign");
    }
}
