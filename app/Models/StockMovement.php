<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_OPENING = 'opening';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALE = 'sale';
    public const TYPE_PROJECT_ISSUE = 'project_issue';
    public const TYPE_PROJECT_RETURN = 'project_return';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_RETURN = 'return';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'ref_id', 'product_id', 'reference', 'type', 'quantity_change',
        'prev_quantity', 'new_quantity', 'unit_cost', 'reason', 'user_id',
        'document_type', 'document_id', 'movement_date',
    ];

    protected $casts = ['movement_date' => 'datetime'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
