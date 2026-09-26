<?php

namespace App\Models\Traits;

use App\Models\Store;
use App\Support\StoreAccess;
use Illuminate\Database\Eloquent\Builder;

trait HasStoreScope
{
    protected static function bootHasStoreScope(): void
    {
        // ── READ: clamp to the stores the current user may actually see ──
        static::addGlobalScope('store', function (Builder $builder) {
            $user = auth()->user();
            $allowed = StoreAccess::allowedStoreIds($user);
            $selected = StoreAccess::selectedStoreId($user);

            if ($selected !== null) {
                $builder->where(static::qualifyStoreColumn(), $selected);
            } elseif ($allowed !== null) {
                // Restricted user viewing "All Stores": still only their own
                // branches. An empty allow-list resolves to no rows.
                $builder->whereIn(static::qualifyStoreColumn(), $allowed);
            }
        });

        // ── WRITE: auto-populate store_id when creating a new record ──────
        static::creating(function ($model) {
            // Self-registered buyers and other intentionally unassigned
            // records opt out so they stay visible under "All Stores" until
            // an admin assigns a branch.
            if (! empty($model->store_id) || ($model->skipStoreAutoAssign ?? false)) {
                return;
            }

            if (empty($model->store_id)) {
                $user = auth()->user();

                // 1. The admin session store selection, when permitted.
                $storeId = StoreAccess::selectedStoreId($user);

                // 2. The authenticated user's allowed branches, first one.
                if (! $storeId) {
                    $storeId = auth()->user()?->allowedStoreIds()[0] ?? null;
                }

                // 3. The system default store, if the user may use it.
                if (! $storeId) {
                    $defaultId = Store::where('is_default', true)->value('id');

                    if ($defaultId && StoreAccess::canAccess($user, (int) $defaultId)) {
                        $storeId = (int) $defaultId;
                    }
                }

                if ($storeId) {
                    $model->store_id = $storeId;
                }
            }
        });
    }

    /**
     * Qualify the store_id column with the table name to avoid ambiguous
     * column errors on queries with joins.
     */
    protected static function qualifyStoreColumn(): string
    {
        return (new static)->getTable().'.store_id';
    }

    public function scopeForCurrentStore(Builder $query): Builder
    {
        return StoreAccess::scope($query, auth()->user(), static::qualifyStoreColumn());
    }

    public function scopeAllStores(Builder $query): Builder
    {
        return $query->withoutGlobalScope('store');
    }
}
