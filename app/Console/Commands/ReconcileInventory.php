<?php

namespace App\Console\Commands;

use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileInventory extends Command
{
    protected $signature = 'inventory:reconcile
                            {tenant : Tenant ID or subdomain}
                            {--force : Fix discrepancies without asking for confirmation}';

    protected $description = 'Reconcile stock levels against stock movements for a tenant';

    public function handle(): int
    {
        $identifier = $this->argument('tenant');
        $tenant = Tenant::query()
            ->where('id', $identifier)
            ->orWhere('subdomain', $identifier)
            ->first();

        if (!$tenant) {
            $this->error("Tenant [{$identifier}] not found.");
            return self::FAILURE;
        }

        request()->attributes->set('tenant', $tenant);

        $expected = [];
        $movements = StockMovement::query()
            ->where('tenant_id', $tenant->id)
            ->get();

        foreach ($movements as $movement) {
            if ($movement->type === 'in') {
                $key = "{$movement->product_id}:{$movement->warehouse_id}";
                $expected[$key] = ($expected[$key] ?? 0) + $movement->quantity;
            }

            if ($movement->type === 'out') {
                $key = "{$movement->product_id}:{$movement->warehouse_id}";
                $expected[$key] = ($expected[$key] ?? 0) - $movement->quantity;
            }

            if ($movement->type === 'transfer') {
                $fromWarehouseId = $movement->meta['from_warehouse_id'];
                $toWarehouseId = $movement->meta['to_warehouse_id'];

                $fromKey = "{$movement->product_id}:{$fromWarehouseId}";
                $toKey = "{$movement->product_id}:{$toWarehouseId}";

                $expected[$fromKey] = ($expected[$fromKey] ?? 0) - $movement->quantity;
                $expected[$toKey] = ($expected[$toKey] ?? 0) + $movement->quantity;
            }
        }

        $actual = StockLevel::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy(fn (StockLevel $stock) =>
            "{$stock->product_id}:{$stock->warehouse_id}"
            );

        $discrepancies = [];
        foreach ($expected as $key => $expectedQuantity) {
            $actualQuantity = $actual->get($key)?->quantity ?? 0;
            if ((int) $expectedQuantity !== (int) $actualQuantity) {
                [$productId, $warehouseId] = explode(':', $key);
                $discrepancies[$key] = [
                    'product_id' => (int) $productId,
                    'warehouse_id' => (int) $warehouseId,
                    'expected' => (int) $expectedQuantity,
                    'actual' => (int) $actualQuantity,
                ];
            }
        }

        foreach ($actual as $key => $stock) {
            if (!array_key_exists($key, $expected) && (int) $stock->quantity !== 0) {
                $discrepancies[$key] = [
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'expected' => 0,
                    'actual' => (int) $stock->quantity,
                ];
            }
        }

        if (empty($discrepancies)) {
            $this->info(
                "No stock discrepancies found for tenant [{$tenant->subdomain}]."
            );

            return self::SUCCESS;
        }

        $this->error(
            "Found " . count($discrepancies) .
            " stock discrepancy(s) for tenant [{$tenant->subdomain}]."
        );

        foreach ($discrepancies as $discrepancy) {
            $this->warn(
                "Product: {$discrepancy['product_id']}, " .
                "Warehouse: {$discrepancy['warehouse_id']}, " .
                "Expected: {$discrepancy['expected']}, " .
                "Actual: {$discrepancy['actual']}"
            );
        }

        if (!$this->option('force') && !$this->confirm('Do you want to fix these discrepancies?')) {
            $this->info('No changes were made.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($discrepancies, $tenant): void {
            foreach ($discrepancies as $discrepancy) {
                $stock = StockLevel::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('product_id', $discrepancy['product_id'])
                    ->where('warehouse_id', $discrepancy['warehouse_id'])
                    ->lockForUpdate()
                    ->first();

                if ($discrepancy['expected'] === 0) {
                    if ($stock) {
                        $stock->update(['quantity' => 0]);
                    }

                    continue;
                }

                if ($stock) {
                    $stock->update([
                        'quantity' => $discrepancy['expected'],
                    ]);
                } else {
                    StockLevel::create([
                        'tenant_id' => $tenant->id,
                        'product_id' => $discrepancy['product_id'],
                        'warehouse_id' => $discrepancy['warehouse_id'],
                        'quantity' => $discrepancy['expected'],
                    ]);
                }
            }
        });

        $this->info('Stock levels have been reconciled successfully.');
        return self::SUCCESS;
    }
}
