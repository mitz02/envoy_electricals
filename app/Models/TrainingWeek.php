<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingWeek extends Model
{
    protected $fillable = [
        'training_id', 'week_number', 'title', 'summary',
    ];

    protected $casts = [
        'week_number' => 'integer',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class)->orderBy('position');
    }
}
