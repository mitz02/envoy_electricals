<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generates sequential, human-readable reference IDs for every important entity.
 *
 * Examples: EV-PROD-000001, SAL-2026-000001, CUS-2026-000001
 */
class ReferenceGenerator
{
    protected static array $prefixes = [
        'product' => 'EV-PROD',
        'sale' => 'SAL',
        'purchase' => 'PUR',
        'expense' => 'EXP',
        'project' => 'PROJ',
        'customer' => 'CUS',
        'supplier' => 'SUP',
        'staff' => 'STF',
        'asset' => 'AST',
        'payment' => 'PAY',
        'stock_movement' => 'STK',
        'adjustment' => 'ADJ',
        'invoice' => 'INV',
        'order' => 'ORD',
        'solar_package' => 'SOL-PKG',
        'solar_calculation' => 'CALC',
        'quotation' => 'QUO',
        'project_payment' => 'PPAY',
        'project_expense' => 'PEXP',
        'payroll' => 'PRL',
    ];

    public static function generate(string $type, ?string $year = null): string
    {
        $prefix = self::$prefixes[$type] ?? strtoupper($type);
        $year = $year ?? now()->format('Y');

        $table = match ($type) {
            'product' => 'products',
            'sale' => 'sales',
            'purchase' => 'purchases',
            'expense' => 'expenses',
            'project' => 'projects',
            'customer' => 'customers',
            'supplier' => 'suppliers',
            'staff' => 'staff',
            'asset' => 'assets',
            'payment' => 'payments',
            'stock_movement' => 'stock_movements',
            'adjustment' => 'stock_movements',
            'invoice' => 'sales',
            'order' => 'orders',
            'solar_package' => 'solar_packages',
            'solar_calculation' => 'solar_calculations',
            'quotation' => 'quotations',
            'project_payment' => 'project_payments',
            'project_expense' => 'project_expenses',
            'payroll' => 'payroll',
            default => null,
        };

        if ($type === 'invoice') {
            $year = now()->format('Y');
            $count = DB::table('sales')
                ->whereYear('sale_date', $year)
                ->whereNull('deleted_at')
                ->count();
            $seq = $count + 1;

            return sprintf('INV-%s-%05d', $year, $seq);
        }

        // Products use a continuous no-year sequence (EV-PROD-000001, EV-PROD-000002, ...).
        $yearly = in_array($type, ['sale', 'purchase', 'expense', 'project', 'payment', 'stock_movement', 'customer', 'supplier', 'staff', 'payroll']);

        if ($yearly && $table) {
            // Use a dedicated sequence stored cleanly: count existing refs with this year prefix.
            $prefixWithYear = $prefix . '-' . $year . '-';
            $like = $prefixWithYear . '%';

            // Only apply a soft-delete filter on models that actually use SoftDeletes.
            $query = DB::table($table)->where('ref_id', 'like', $like);
            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            // SQLite and MySQL both support LIKE here.
            $count = $query->count();

            $seq = $count + 1;

            return sprintf('%s-%s-%06d', $prefix, $year, $seq);
        }

        if ($type === 'product') {
            $count = DB::table('products')->whereNull('deleted_at')->count();
            return sprintf('EV-PROD-%06d', $count + 1);
        }

        $count = DB::table($table ?? 'products')->count();
        $seq = $count + 1;
        $yearFull = $year;

        return sprintf('%s-%s-%06d', $prefix, $yearFull, $seq);
    }

    public static function referenceFor(string $type, string $refPrefix): string
    {
        $last = DB::table('stock_movements')
            ->where('ref_id', 'like', $refPrefix . '%')
            ->orderByDesc('id')
            ->value('ref_id');

        preg_match('/(\d+)$/', (string) $last, $m);
        $next = ((int) ($m[1] ?? 0)) + 1;

        return $refPrefix . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
