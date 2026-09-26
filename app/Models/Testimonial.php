<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = [
        'feedback_id', 'author_name', 'author_role', 'content', 'rating',
        'photo_path', 'is_published',
    ];

    protected $casts = [
        'rating' => 'int',
        'is_published' => 'boolean',
    ];

    protected $attributes = [
        'is_published' => false,
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }
}
