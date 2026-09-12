<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainee extends Model
{
    use SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_GRADUATED = 'graduated';
    const STATUS_WITHDRAWN = 'withdrawn';

    const TYPE_STAFF = 'staff';
    const TYPE_APPRENTICE = 'apprentice';
    const TYPE_TRAINEE = 'trainee';

    protected $fillable = [
        'ref_id', 'user_id', 'staff_id', 'type', 'name', 'phone', 'email',
        'date_of_birth', 'gender', 'address', 'city', 'education', 'occupation',
        'emergency_contact_name', 'emergency_contact_phone', 'notes', 'status',
        'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class, 'enrollments')
            ->withPivot(['id', 'ref_id', 'enrolled_at', 'status', 'progress', 'grade', 'notes']);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name));

        $initials = array_map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)), $parts ?: []);

        return implode('', array_slice($initials, 0, 2));
    }
}