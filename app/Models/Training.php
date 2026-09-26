<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use SoftDeletes;

    const LEVEL_BEGINNER = 'beginner';

    const LEVEL_INTERMEDIATE = 'intermediate';

    const LEVEL_ADVANCED = 'advanced';

    protected $fillable = [
        'ref_id', 'title', 'description', 'image_path', 'prerequisites', 'objectives',
        'learning_outcomes', 'curriculum', 'duration_weeks', 'start_date',
        'level', 'price', 'capacity', 'is_active', 'certificate_eligible',
        'is_featured', 'created_by',
    ];

    protected $casts = [
        'duration_weeks' => 'integer',
        'price' => 'float',
        'capacity' => 'integer',
        'start_date' => 'date',
        'is_active' => 'boolean',
        'certificate_eligible' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(TrainingWeek::class)->orderBy('week_number');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(TrainingLesson::class, TrainingWeek::class)
            ->orderBy('training_weeks.week_number')
            ->orderBy('training_lessons.position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function trainees(): BelongsToMany
    {
        return $this->belongsToMany(Trainee::class, 'enrollments')
            ->withPivot(['id', 'ref_id', 'enrolled_at', 'status', 'progress', 'grade', 'notes']);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getEnrolledCountAttribute(): int
    {
        return $this->enrollments()->where('status', '!=', Enrollment::STATUS_WITHDRAWN)->count();
    }
}
