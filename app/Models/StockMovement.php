<?php

namespace App\Models;

use App\Models\Traits\HasStoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasStoreScope;

    public const TYPE_OPENING = 'opening';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE = 'sale';

    public const TYPE_PROJECT_ISSUE = 'project_issue';

    public const TYPE_PROJECT_RETURN = 'project_return';

    public const TYPE_DAMAGE = 'damage';

    public const TYPE_RETURN = 'return';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    protected $fillable = [
        'ref_id', 'product_id', 'store_id', 'from_store_id', 'to_store_id', 'reference', 'type', 'quantity_change',
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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }
}
