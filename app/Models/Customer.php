<?php

namespace App\Models;

use App\Models\Traits\HasStoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasStoreScope, SoftDeletes;

    /**
     * When true, HasStoreScope's write hook will not auto-assign a branch
     * ("store_id") on create. Used for self-registered buyers and any record
     * created from the admin form as "Unassigned".
     */
    public bool $skipStoreAutoAssign = false;

    protected $fillable = [
        'ref_id', 'user_id', 'name', 'phone', 'email', 'address', 'location',
        'customer_type', 'store_id', 'notes',
    ];

    protected $casts = ['customer_type' => 'string'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Total amount this customer still owes across unpaid (completed) invoices.
     */
    public function outstandingBalance(): float
    {
        return (float) $this->sales()->where('status', 'completed')->sum('balance');
    }

    public function getHasOutstandingAttribute(): bool
    {
        return ((float) ($this->outstanding ?? $this->outstandingBalance())) > 0;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
