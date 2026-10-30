<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\StockMovement;
use App\Services\SaleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

#[Signature('orders:backfill-sales
    {--dry-run : Report what would be created without writing anything}
    {--date-from= : Only orders delivered on or after this date (Y-m-d)}')]
#[Description('Generate the missing sale invoice for storefront orders that were already delivered')]
class BackfillDeliveredOrdersToSales extends Command
{
    public function handle(SaleService $sales): int
    {
        $query = Order::with('items.product')
            ->where('fulfillment', 'delivered')
            ->whereNull('linked_sale_id')
            ->orderBy('id');

        if ($from = $this->option('date-from')) {
            $query->where('updated_at', '>=', Carbon::parse($from)->startOfDay());
        }

        $orders = $query->get();
        $dryRun = (bool) $this->option('dry-run');

        if ($orders->isEmpty()) {
            $this->info('No delivered orders are missing a sale. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d delivered order(s) without a sale.', $orders->count()));

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        $created = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $deliveredOn = $this->deliveredOn($order);

            if ($dryRun) {
                $this->line(sprintf(
                    '  would create sale for %s (total %s, delivered %s)',
                    $order->ref_id,
                    number_format((float) $order->total, 2),
                    $deliveredOn?->toDateString() ?? 'unknown',
                ));
                $created++;
                $bar->advance();

                continue;
            }

            try {
                // No actor: this is a historical repair, not a staff action.
                $sales->createSaleFromOrder($order, null, $deliveredOn?->toDateString());
                $created++;
            } catch (Throwable $e) {
                $skipped++;
                $this->newLine();
                $this->warn("  {$order->ref_id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $summary = sprintf('%s %d sale(s).', $dryRun ? 'Would create' : 'Created', $created);

        if ($skipped > 0) {
            $summary .= sprintf(' Skipped %d — see warnings above.', $skipped);
        }

        $this->info($summary);

        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * When the goods actually went out.
     *
     * The orders table has no delivered_at column, but the stock movements
     * written by fulfillOrder record the exact moment, so prefer those and fall
     * back to updated_at if the movements are missing.
     */
    protected function deliveredOn(Order $order): ?Carbon
    {
        $movementDate = StockMovement::query()
            ->where('document_type', 'order')
            ->where('document_id', $order->id)
            ->where('type', StockMovement::TYPE_SALE)
            ->min('movement_date');

        return $movementDate
            ? Carbon::parse($movementDate)
            : $order->updated_at;
    }
}
