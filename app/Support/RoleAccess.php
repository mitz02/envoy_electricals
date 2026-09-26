<?php

namespace App\Support;

use App\Models\Role;

/**
 * Display-level field masking driven by each role's access_config.
 *
 * Super admins (owner) always see the real value. When the current user's
 * role marks a field as masked, renders are replaced with the mask glyph.
 */
final class RoleAccess
{
    public const MASK_GLYPH = '••••••';

    /**
     * Sentinel returned when a field is masked for the current user.
     */
    public const MASKED = '__MASKED__';

    /**
     * Human friendly definitions shown on the management page.
     *
     * @return array<int, array{slug: string, label: string, hint: string, type: string}>
     */
    public static function maskableFields(): array
    {
        return [
            [
                'slug' => 'cost_price',
                'label' => 'Product cost price',
                'hint' => 'Purchase cost of products',
                'type' => 'money',
            ],
            [
                'slug' => 'gross_profit',
                'label' => 'Gross profit',
                'hint' => 'Gross profit on sales and projects',
                'type' => 'money',
            ],
            [
                'slug' => 'net_profit',
                'label' => 'Net profit',
                'hint' => 'Net profit after expenses',
                'type' => 'money',
            ],
            [
                'slug' => 'profit_margin',
                'label' => 'Profit margin (%)',
                'hint' => 'Margin percentage figures',
                'type' => 'percent',
            ],
            [
                'slug' => 'sales_profit',
                'label' => 'Per-sale profit',
                'hint' => 'Profit earned on each transaction',
                'type' => 'money',
            ],
            [
                'slug' => 'receivables',
                'label' => 'Receivables & payables',
                'hint' => 'Money owed to/from the business',
                'type' => 'money',
            ],
            [
                'slug' => 'inventory_value',
                'label' => 'Inventory valuation',
                'hint' => 'Stock value based on cost prices',
                'type' => 'money',
            ],
            [
                'slug' => 'salary',
                'label' => 'Payroll & salaries',
                'hint' => 'Staff salary figures',
                'type' => 'money',
            ],
        ];
    }

    public static function maskableSlugs(): array
    {
        return collect(self::maskableFields())->pluck('slug')->all();
    }

    /**
     * Resolve the mask glyph if the given slug is masked for the current user,
     * otherwise return the original value (via $value or lazy $resolver).
     *
     * @param  null|\Closure(): mixed  $resolver
     */
    public static function resolve(?Role $role, string $slug, mixed $value = null, ?\Closure $resolver = null): mixed
    {
        if ($role === null || $role->slug === 'owner' || ! $role->masksField($slug)) {
            return $resolver ? $resolver() : $value;
        }

        return self::MASK_GLYPH;
    }
}
