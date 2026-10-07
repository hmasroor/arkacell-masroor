<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Exceptions\InsufficientStockException;

class InventoryService
{
    public function in(Product $product, Warehouse $warehouse, int $quantity, ?string $reference = null): StockMovement {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $reference) {
            $stock = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                $stock = StockLevel::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => 0,
                ]);
            }

            $stock->increment('quantity', $quantity);

            return StockMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'quantity' => $quantity,
                'reference' => $reference,
            ]);
        });
    }

    public function out(Product $product, Warehouse $warehouse, int $quantity, ?string $reference = null): StockMovement {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $reference) {
            $stock = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (!$stock || $stock->quantity < $quantity) {
                throw new InsufficientStockException($product, $warehouse, $quantity, $stock->quantity ?? 0);
            }

            $stock->decrement('quantity', $quantity);

            return StockMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'out',
                'quantity' => $quantity,
                'reference' => $reference,
            ]);
        });
    }

    public function transfer(Product $product, Warehouse $fromWarehouse, Warehouse $toWarehouse, int $quantity, ?string $reference = null): StockMovement {
        return DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $quantity, $reference) {
            if ($fromWarehouse->id === $toWarehouse->id) {
                throw new \InvalidArgumentException(
                    'Source and destination warehouses cannot be same'
                );
            }

            $sourceStock = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $fromWarehouse->id)
                ->lockForUpdate()
                ->first();

            $availableQuantity = $sourceStock?->quantity ?? 0;
            if ($availableQuantity < $quantity) {
                throw new InsufficientStockException($product, $fromWarehouse, $quantity, $availableQuantity);
            }

            $sourceStock->decrement('quantity', $quantity);

            $targetStock = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $toWarehouse->id)
                ->lockForUpdate()
                ->first();

            if (!$targetStock) {
                $targetStock = StockLevel::create([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                    'warehouse_id' => $toWarehouse->id,
                    'quantity' => 0,
                ]);
            }

            $targetStock->increment('quantity', $quantity);

            return StockMovement::create([
                'tenant_id' => $product->tenant_id,
                'product_id' => $product->id,
                'warehouse_id' => $toWarehouse->id,
                'type' => 'transfer',
                'quantity' => $quantity,
                'reference' => $reference,
                'meta' => [
                    'from_warehouse_id' => $fromWarehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                ],
            ]);
        });
    }
}
