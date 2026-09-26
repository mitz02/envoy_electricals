<?php

namespace App\Support;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves which stores the signed-in user may see and keeps the selected
 * store in the session inside that allow-list.
 *
 * A super admin (owner role) is never restricted. Everyone else is limited to
 * their `user_store` allow-list, or to their single `store_id` branch when no
 * allow-list was granted, and is unrestricted when neither is set.
 */
final class StoreAccess
{
    public const SESSION_KEY = 'admin_store_id';

    /**
     * @return array<int, int>|null Null means unrestricted.
     */
    public static function allowedStoreIds(?User $user): ?array
    {
        return $user?->allowedStoreIds();
    }

    public static function isRestricted(?User $user): bool
    {
        return $user !== null && $user->isStoreRestricted();
    }

    public static function canAccess(?User $user, ?int $storeId): bool
    {
        if ($user === null) {
            return true;
        }

        return $user->canAccessStore($storeId);
    }

    /**
     * Stores the user is allowed to pick from, active ones only.
     */
    public static function query(?User $user): Builder
    {
        $query = Store::query()->orderBy('name');
        $allowed = self::allowedStoreIds($user);

        return $allowed === null ? $query : $query->whereIn('id', $allowed);
    }

    public static function activeQuery(?User $user): Builder
    {
        return self::query($user)->where('is_active', true);
    }

    /**
     * The store currently selected in the session, corrected so it always
     * falls inside the user's allow-list.
     */
    public static function selectedStoreId(?User $user): ?int
    {
        $selected = session(self::SESSION_KEY);
        $selected = $selected === null ? null : (int) $selected;

        return self::canAccess($user, $selected) ? $selected : null;
    }

    /**
     * Write the selected store back to the session, clamped to what the user
     * may access. Returns the store id that is now selected.
     */
    public static function syncSession(?User $user): ?int
    {
        $selected = self::selectedStoreId($user);

        if ($selected === null) {
            session()->forget(self::SESSION_KEY);
        } elseif (session(self::SESSION_KEY) !== $selected) {
            session([self::SESSION_KEY => $selected]);
        }

        return $selected;
    }

    /**
     * Constrain a store-scoped query to what the user may see.
     *
     * When a single store is selected the query is pinned to it, otherwise a
     * restricted user is limited to their whole allow-list.
     *
     * @param  Builder  $query  Query on a model exposing a store_id column.
     * @param  string  $column  Fully qualified store column.
     */
    public static function scope(Builder $query, ?User $user, string $column, ?int $selected = null): Builder
    {
        $selected ??= self::selectedStoreId($user);

        if ($selected !== null) {
            return $query->where($column, $selected);
        }

        $allowed = self::allowedStoreIds($user);

        return $allowed === null ? $query : $query->whereIn($column, $allowed);
    }
}
