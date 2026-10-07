<?php

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $request = Request::create('/');
    $request->attributes->set('tenant', $this->tenant);
    $this->app->instance('request', $request);
    $this->product = Product::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->warehouse = Warehouse::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);
    $this->service = app(InventoryService::class);
});

it('adds stock', function () {
    StockLevel::create([
        'tenant_id' => $this->tenant->id,
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
    ]);
    $this->service->in($this->product, $this->warehouse, 5);
    $this->assertDatabaseHas('stock_levels', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 15,
    ]);
});

it('creates stock level when it does not exist', function () {
    $this->service->in($this->product, $this->warehouse, 10);
    $this->assertDatabaseHas('stock_levels', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
    ]);
});

it('removes stock', function () {
    StockLevel::create([
        'tenant_id' => $this->tenant->id,
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
    ]);
    $this->service->out($this->product, $this->warehouse, 3);
    $this->assertDatabaseHas('stock_levels', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 7,
    ]);
});

it('throws an exception when stock is insufficient', function () {
    StockLevel::create([
        'tenant_id' => $this->tenant->id,
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 5,
    ]);
    expect(fn () => $this->service->out(
        $this->product,
        $this->warehouse,
        6,
    ))->toThrow(InsufficientStockException::class);
});

it('does not change stock when stock is insufficient', function () {
    StockLevel::create([
        'tenant_id' => $this->tenant->id,
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 5,
    ]);
    expect(fn () => $this->service->out(
        $this->product,
        $this->warehouse,
        6,
    ))->toThrow(InsufficientStockException::class);
    $this->assertDatabaseHas('stock_levels', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 5,
    ]);
    $this->assertDatabaseCount('stock_movements', 0);
});

it('cannot remove stock when stock level does not exist', function () {
    expect(fn () => $this->service->out(
        $this->product,
        $this->warehouse,
        1,
    ))->toThrow(InsufficientStockException::class);
    $this->assertDatabaseCount('stock_movements', 0);
});
