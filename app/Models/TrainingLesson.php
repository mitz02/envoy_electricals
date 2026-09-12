<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingLesson extends Model
{
    protected $fillable = [
        'training_week_id', 'title', 'description', 'objectives', 'duration_minutes', 'position',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'position' => 'integer',
    ];

    public function week(): BelongsTo
    {
        return $this->belongsTo(TrainingWeek::class, 'training_week_id');
    }
}