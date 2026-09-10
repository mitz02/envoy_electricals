<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'user_id', 'name', 'position', 'phone', 'email',
        'date_joined', 'base_salary', 'housing_allowance', 'transport_allowance',
        'other_allowance', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_joined' => 'date',
        'base_salary' => 'float',
        'housing_allowance' => 'float',
        'transport_allowance' => 'float',
        'other_allowance' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function paidPayrolls(): HasMany
    {
        return $this->payrolls()->where('status', Payroll::STATUS_PAID);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getTotalAllowanceAttribute(): float
    {
        return round((float) $this->housing_allowance + (float) $this->transport_allowance + (float) $this->other_allowance, 2);
    }

    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->paidPayrolls()->sum('amount_paid'), 2);
    }

    public function getTotalBonusesAttribute(): float
    {
        return round((float) $this->paidPayrolls()->sum('bonus'), 2);
    }

    public function getTotalAdvancesAttribute(): float
    {
        return round((float) $this->paidPayrolls()->sum('advance'), 2);
    }

    public function getTotalDeductionsAttribute(): float
    {
        return round((float) $this->paidPayrolls()->sum('deduction'), 2);
    }
}