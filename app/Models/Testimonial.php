<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Testimonial extends Model
{
    protected $fillable = [
        'feedback_id', 'author_name', 'author_role', 'content', 'rating',
        'photo_path', 'is_published', 'token', 'status', 'email', 'phone', 'admin_notes',
    ];

    protected $casts = [
        'rating' => 'int',
        'is_published' => 'boolean',
    ];

    protected $attributes = [
        'is_published' => false,
        'status' => 'pending',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }

    public static function generateToken(): string
    {
        return Str::random(40);
    }

    public function getPublicSubmissionUrl(): string
    {
        return url("/testimonial/submit/{$this->token}");
    }
}
