<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'expense_date', 'expense_category_id', 'description',
        'amount', 'payment_method', 'paid_to', 'reference_no', 'staff_id',
        'remarks', 'receipt_path', 'media_id', 'status', 'created_by',
    ];

    protected $casts = ['expense_date' => 'date'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
