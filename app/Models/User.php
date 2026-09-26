<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'is_active', 'phone', 'store_id',
    ];

    /** @var array<int, int>|null|false Memoized result of allowedStoreIds(). */
    protected $allowedStoreIdsCache = false;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Every store this user may be granted access to.
     *
     * This is the multi-branch allow-list a super admin maintains. It is
     * separate from the single `store_id` home branch, which only records
     * where the person is primarily based.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'user_store')->withTimestamps();
    }

    public function trainee(): HasOne
    {
        return $this->hasOne(Trainee::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notificationsStored(): HasMany
    {
        return $this->hasMany(NotificationStored::class, 'user_id');
    }

    public function unreadStoredNotifications(): HasMany
    {
        return $this->notificationsStored()->where('is_read', false);
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->directPermissions()->where('permissions.slug', $permissionSlug)->exists()
            || ($this->role && $this->role->permissions()->where('permissions.slug', $permissionSlug)->exists());
    }

    public function hasAnyPermission(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->slug === 'owner';
    }

    /**
     * Store ids this user is allowed to see, or null when unrestricted.
     *
     * A super admin is never restricted. Otherwise we merge:
     * 1. Explicit `user_store` allow-list (highest priority)
     * 2. Role's `allowed_store_ids` from access_config
     * 3. Legacy single `store_id` on the user model
     *
     * @return array<int, int>|null
     */
    public function allowedStoreIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        if ($this->allowedStoreIdsCache === false) {
            $granted = $this->stores()->pluck('stores.id')->map(fn ($id) => (int) $id)->all();

            if ($granted === [] && $this->role) {
                $roleAllowed = $this->role->allowedStoreIds();
                if ($roleAllowed !== null) {
                    $granted = $roleAllowed;
                }
            }

            if ($granted === []) {
                $granted = $this->store_id ? [(int) $this->store_id] : [];
            }

            $this->allowedStoreIdsCache = $granted === [] ? null : $granted;
        }

        return $this->allowedStoreIdsCache;
    }

    /**
     * Whether this user is limited to a subset of stores.
     */
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

    /**
     * Replace this user's store allow-list. Passing an empty array clears the
     * restriction and restores access to every store, so the legacy home
     * branch is dropped too — otherwise the fallback in allowedStoreIds()
     * would quietly keep the user pinned to a single store.
     *
     * @param  array<int, int>  $storeIds
     */
    public function syncAllowedStores(array $storeIds): void
    {
        $storeIds = array_values(array_unique(array_map('intval', $storeIds)));

        $this->stores()->sync($storeIds);

        if ($storeIds === [] && $this->store_id !== null) {
            $this->forceFill(['store_id' => null])->save();
        }

        $this->allowedStoreIdsCache = false;
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->directPermissions()->where('permissions.module', $module)->exists()
            || ($this->role && $this->role->permissions()->where('permissions.module', $module)->exists());
    }
}
