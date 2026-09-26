<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'phone', 'email', 'is_active', 'is_default', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Branch grants live in the `user_store` pivot, mirroring User::stores().
     * The legacy `users.store_id` column is only a "home branch" hint and is
     * cleared when a user has no grants, so it must not be used here.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_store')->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_store')
            ->withPivot('current_quantity', 'reorder_level', 'average_cost', 'selling_price')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
