<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Feedback extends Model
{
    protected $fillable = [
        'user_id', 'customer_id', 'customer_name', 'customer_email', 'rating',
        'comment', 'experience', 'challenge', 'suggestion', 'status',
        'admin_response',
    ];

    protected $casts = [
        'rating' => 'int',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }
}