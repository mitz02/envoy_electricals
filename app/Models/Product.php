<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_id', 'sku', 'name', 'category_id', 'subcategory_id', 'brand_id',
        'description', 'specifications', 'unit', 'barcode', 'supplier_id',
        'cost_price', 'selling_price', 'average_cost', 'current_quantity',
        'reorder_level', 'status', 'is_featured', 'is_visible_online',
        'allow_online_purchase', 'specifications_json',
    ];

    protected $casts = [
        'cost_price' => 'float',
        'selling_price' => 'float',
        'average_cost' => 'float',
        'current_quantity' => 'integer',
        'reorder_level' => 'integer',
        'is_featured' => 'boolean',
        'is_visible_online' => 'boolean',
        'allow_online_purchase' => 'boolean',
        'specifications_json' => 'array',
    ];

    protected $appends = ['stock_status', 'stock_value'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'subcategory_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'product_store')
            ->withPivot('current_quantity', 'reorder_level', 'average_cost', 'selling_price')
            ->withTimestamps();
    }

    public function isLowStock(): bool
    {
        return $this->current_quantity <= $this->reorder_level;
    }

    public function stockStatus(): Attribute
    {
        return Attribute::get(fn () => $this->current_quantity <= 0
            ? 'out_of_stock'
            : ($this->current_quantity <= $this->reorder_level ? 'low_stock' : 'in_stock'));
    }

    public function stockValue(): Attribute
    {
        return Attribute::get(fn () => round($this->current_quantity * $this->average_cost, 2));
    }
}
