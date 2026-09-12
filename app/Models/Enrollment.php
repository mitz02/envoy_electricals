<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    const STATUS_ENROLLED = 'enrolled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'ref_id', 'trainee_id', 'training_id', 'enrolled_at', 'status',
        'progress', 'grade', 'notes', 'created_by',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'progress' => 'integer',
        'grade' => 'float',
    ];

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(Trainee::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}