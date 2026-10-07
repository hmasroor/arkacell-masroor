<?php

namespace App\Jobs;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class ConcurrentStockOutJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $productId,
        public int $warehouseId,
        public int $tenantId,
        public string $resultKey,
    ) {
    }

    public function handle(InventoryService $inventory): void
    {
        $request = request();
        $request->attributes->set('tenant', Tenant::findOrFail($this->tenantId),);

        try {
            $product = Product::withoutGlobalScopes()
                ->where('tenant_id', $this->tenantId)
                ->findOrFail($this->productId);

            $warehouse = Warehouse::withoutGlobalScopes()
                ->where('tenant_id', $this->tenantId)
                ->findOrFail($this->warehouseId);

            $inventory->out($product, $warehouse, 1);
            Cache::increment($this->resultKey . ':success');
        } catch (InsufficientStockException) {
            Cache::increment($this->resultKey . ':failed');
        }
    }
}
