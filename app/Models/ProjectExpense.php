<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpense extends Model
{
    public const TYPE_LABOUR = 'labour';

    public const TYPE_TRANSPORT = 'transport';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'ref_id', 'project_id', 'expense_type', 'amount',
        'expense_date', 'payee', 'notes', 'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
