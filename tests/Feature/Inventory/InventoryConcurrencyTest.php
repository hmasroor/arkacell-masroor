<?php

use App\Jobs\ConcurrentStockOutJob;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\DatabaseMigrations;

// For not deleting 50 database rows
//uses(DatabaseMigrations::class);

it('allows only 50 of 100 concurrent stock outs', function () {
    $tenant = Tenant::factory()->create();
    $request = Request::create('/');
    $request->attributes->set('tenant', $tenant);
    $this->app->instance('request', $request);
    $product = Product::factory()->create(['tenant_id' => $tenant->id]);
    $warehouse = Warehouse::factory()->create(['tenant_id' => $tenant->id]);

    StockLevel::create([
        'tenant_id' => $tenant->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 50,
    ]);

    $resultKey = 'concurrency-test-' . uniqid();

    Cache::put($resultKey . ':success', 0);
    Cache::put($resultKey . ':failed', 0);

    for ($i = 0; $i < 100; $i++) {
        ConcurrentStockOutJob::dispatch(
            $product->id,
            $warehouse->id,
            $tenant->id,
            $resultKey,
        )->onQueue('concurrency-test');
    }

    $workers = [];
    for ($i = 0; $i < 10; $i++) {
        $workers[] = proc_open(
            [
                PHP_BINARY,
                'artisan',
                'queue:work',
                'redis',
                '--queue=concurrency-test',
                '--sleep=0',
                '--tries=1',
                '--stop-when-empty',
            ],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ],
            $pipes,
        );
    }

    $timeout = microtime(true) + 30;

    while (
        Cache::get($resultKey . ':success', 0)
        + Cache::get($resultKey . ':failed', 0) < 100
    ) {
        if (microtime(true) > $timeout) {
            $this->fail('Concurrency test timed out.');
        }

        usleep(100_000);
    }

    foreach ($workers as $worker) {
        proc_close($worker);
    }

    $successes = (int) Cache::get($resultKey . ':success', 0);
    $failures = (int) Cache::get($resultKey . ':failed', 0);

    expect($successes)->toBe(50)
        ->and($failures)->toBe(50)
        ->and(
            StockLevel::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('quantity')
        )->toBe(0);
});
