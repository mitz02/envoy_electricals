<?php

namespace App\Http\Middleware;

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
                    'permissions' => $user->isSuperAdmin()
                        ? ['*']
                        : $user->directPermissions()
                            ->pluck('slug')
                            ->merge($user->role?->permissions()->pluck('slug') ?? collect())
                            ->unique()
                            ->values()
                            ->all(),
                ] : null,
            ],
            'settings' => $this->settings(),
        ];
    }

    protected function settings(): array
    {
        if (! Schema::hasTable('settings')) {
            return ['business' => [], 'currency' => '₦', 'tax_rate' => 0];
        }

        return [
            'business' => cache()->remember('settings.business', 3600, function () {
                return \App\Models\Setting::where('group', 'business')->pluck('value', 'key');
            }),
            'bank' => cache()->remember('settings.bank', 3600, function () {
                return \App\Models\Setting::where('group', 'bank')->pluck('value', 'key');
            }),
            'currency' => \App\Models\Setting::where('key', 'currency.symbol')->value('value') ?? '₦',
            'tax_rate' => (float) (\App\Models\Setting::where('key', 'tax.rate')->value('value') ?? 0),
        ];
    }
}