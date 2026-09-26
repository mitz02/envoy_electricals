<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Newsletter extends Model
{
    protected $fillable = [
        'subject', 'content', 'image_path', 'status', 'scheduled_at',
        'sent_at', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
