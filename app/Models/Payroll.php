<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $table = 'payroll';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ref_id', 'staff_id', 'period_month', 'period_year', 'base_salary',
        'allowance', 'bonus', 'advance', 'deduction', 'amount_paid',
        'payment_date', 'payment_method', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'base_salary' => 'float',
        'allowance' => 'float',
        'bonus' => 'float',
        'advance' => 'float',
        'deduction' => 'float',
        'amount_paid' => 'float',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'document_id')
            ->where('document_type', 'payroll');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForPeriod(Builder $query, string $year, string $month): Builder
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function getPeriodAttribute(): string
    {
        return sprintf('%s-%02s', $this->period_year, str_pad((string) $this->period_month, 2, '0', STR_PAD_LEFT));
    }

    public function getPeriodLabelAttribute(): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->period)->format('F Y');
    }
}