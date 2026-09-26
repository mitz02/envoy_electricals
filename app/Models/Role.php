<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'access_config'];

    protected $casts = [
        'access_config' => 'array',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function maskedFields(): array
    {
        return $this->access_config['masked_fields'] ?? [];
    }

    public function masksField(string $slug): bool
    {
        return in_array($slug, $this->maskedFields(), true);
    }

    public function allowedStoreIds(): ?array
    {
        $ids = $this->access_config['allowed_store_ids'] ?? null;

        if ($ids === null || $ids === []) {
            return null;
        }

        return array_map('intval', $ids);
    }

    public function isStoreRestricted(): bool
    {
        return $this->allowedStoreIds() !== null;
    }

    public function canAccessStore(?int $storeId): bool
    {
        $allowed = $this->allowedStoreIds();

        if ($allowed === null) {
            return true;
        }

        return $storeId !== null && in_array((int) $storeId, $allowed, true);
    }
}
