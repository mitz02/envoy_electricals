<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMaterial extends Model
{
    protected $fillable = [
        'project_id', 'product_id', 'quantity', 'unit_cost', 'total',
        'issued_date', 'issued_to_inventory',
    ];

    protected $casts = ['issued_date' => 'date', 'issued_to_inventory' => 'boolean'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
