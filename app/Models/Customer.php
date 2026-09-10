<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'name', 'phone', 'email', 'address', 'location',
        'customer_type', 'notes',
    ];

    protected $casts = ['customer_type' => 'string'];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
