<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomQuotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'customer_company',
        'quotation_date',
        'valid_until',
        'job_type',
        'title',
        'description',
        'notes',
        'subtotal',
        'discount',
        'tax',
        'other_charges',
        'grand_total',
        'status',
        'project_id',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CustomQuotationItem::class, 'custom_quotation_id')->orderBy('sort_order');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('ref_id', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%")
                ->orWhere('customer_email', 'like', "%{$search}%")
                ->orWhere('customer_company', 'like', "%{$search}%")
                ->orWhere('job_type', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%");
        });
    }

    public static function generateRefId(): string
    {
        $prefix = 'QT-'.date('Y');
        $last = self::where('ref_id', 'like', $prefix.'-%')
            ->orderBy('id', 'desc')
            ->first();

        $number = $last ? (int) substr($last->ref_id, -4) + 1 : 1;

        return $prefix.'-'.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->grand_total = $this->subtotal - $this->discount + $this->tax + $this->other_charges;
        $this->save();
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'draft' => 'bg-slate-100 text-slate-600',
            'sent' => 'bg-sky-100 text-sky-700',
            'viewed' => 'bg-blue-100 text-blue-700',
            'accepted' => 'bg-emerald-100 text-emerald-800',
            'rejected' => 'bg-red-100 text-red-700',
            'expired' => 'bg-amber-100 text-amber-800',
            'converted' => 'bg-emerald-100 text-emerald-800',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'viewed' => 'Viewed',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'converted' => 'Converted to Project',
            default => ucfirst($this->status),
        };
    }
}
