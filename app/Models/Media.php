<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'path', 'type', 'mime', 'size', 'category', 'caption', 'alt', 'uploaded_by',
    ];
}
