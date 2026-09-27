<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Models\Setting;
use App\Models\SolarPackage;
use App\Models\User;
use App\Support\StoreAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        // Clamp the session store selection to whatever this user may access.
        $selectedStoreId = StoreAccess::syncSession($user);
        $stores = $user
            ? StoreAccess::query($user)->get(['id', 'name', 'code', 'is_active'])
            : collect();

        $selectedStore = $selectedStoreId
            ? $stores->firstWhere('id', $selectedStoreId)
            : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role?->name,
                    'role_slug' => $user->role?->slug,
                    'store_id' => $user->store_id,
                    'store_access' => $user->allowedStoreIds(),
                    'is_store_restricted' => $user->isStoreRestricted(),
                    'masked_fields' => $user->isSuperAdmin()
                        ? []
                        : ($user->role?->maskedFields() ?? []),
                    'permissions' => $user->isSuperAdmin()
                        ? ['*']
                        : $user->directPermissions()
                            ->pluck('slug')
                            ->concat($user->role?->permissions()->pluck('slug') ?? collect())
                            ->unique()
                            ->values()
                            ->all(),
                ] : null,
            ],
            'notifications' => $user ? [
                'unread_count' => $user->unreadStoredNotifications()->count(),
                'items' => $user->notificationsStored()
                    ->latest('id')
                    ->limit(5)
                    ->get(['id', 'title', 'body', 'type', 'link', 'is_read', 'created_at']),
            ] : null,
            'impersonation' => $user && session()->has('impersonator_id')
                ? [
                    'active' => true,
                    'impersonator_name' => User::find((int) session('impersonator_id'))?->name,
                ]
                : ['active' => false, 'impersonator_name' => null],
            'settings' => $this->settings(),
            'stores' => $stores,
            'selectedStore' => $selectedStore,
            // Calculator-specific data: available products for recommendations
            'products' => fn () => Product::where('allow_online_purchase', true)
                ->where('is_visible_online', true)
                ->where('current_quantity', '>', 0)
                ->get(['id', 'name', 'sku', 'selling_price', 'specifications_json', 'current_quantity', 'category_id']),
            'solarPackages' => fn () => SolarPackage::where('is_visible_online', true)
                ->where('availability', 'available')
                ->with('items')
                ->get(['id', 'ref_id', 'name', 'description', 'package_price', 'installation_cost', 'estimated_load_capacity', 'inverter_capacity', 'warranty', 'availability']),
        ];
    }

    protected function settings(): array
    {
        if (! Schema::hasTable('settings')) {
            return ['business' => [], 'bank' => [], 'currency' => '₦', 'tax_rate' => 0, 'calculator' => []];
        }

        return [
            'business' => cache()->remember('settings.business', 3600, function () {
                return Setting::where('group', 'business')->pluck('value', 'key');
            }),
            'bank' => cache()->remember('settings.bank', 3600, function () {
                return Setting::where('group', 'bank')->pluck('value', 'key');
            }),
            'currency' => Setting::where('key', 'currency.symbol')->value('value') ?? '₦',
            'tax_rate' => (float) (Setting::where('key', 'tax.rate')->value('value') ?? 0),
            // Calculator settings: NO CACHE - must reflect admin changes immediately
            'calculator' => Setting::where('group', 'calculator')->pluck('value', 'key'),
        ];
    }
}
