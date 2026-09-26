<?php

namespace App\Models;

use App\Models\Traits\HasStoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasStoreScope, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUOTATION = 'quotation';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_INSTALLATION = 'installation';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const ACTIVE_STATUSES = [
        self::STATUS_APPROVED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_INSTALLATION,
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_QUOTATION,
        self::STATUS_APPROVED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_INSTALLATION,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'ref_id', 'name', 'description', 'customer_id', 'customer_address',
        'location', 'store_id', 'contract_value', 'material_cost', 'labour_cost',
        'transport_cost', 'other_cost', 'project_cost', 'amount_received',
        'balance', 'gross_profit', 'start_date', 'expected_completion_date',
        'completion_date', 'status', 'assigned_user_id', 'technician_user_id',
        'published', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_completion_date' => 'date',
        'completion_date' => 'date',
        'published' => 'boolean',
        'contract_value' => 'float',
        'material_cost' => 'float',
        'labour_cost' => 'float',
        'transport_cost' => 'float',
        'other_cost' => 'float',
        'project_cost' => 'float',
        'amount_received' => 'float',
        'balance' => 'float',
        'gross_profit' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function technicianUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProjectMaterial::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProjectPayment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProjectMedia::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
