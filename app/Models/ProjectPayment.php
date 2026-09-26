<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPayment extends Model
{
    protected $fillable = [
        'ref_id', 'project_id', 'payment_date', 'amount',
        'payment_method', 'reference', 'remarks',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
