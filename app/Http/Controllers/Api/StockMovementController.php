<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StockMovementRequest;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Http\Resources\StockMovementResource;

class StockMovementController extends Controller
{
    public function store(StockMovementRequest $request, InventoryService $inventory): StockMovementResource {
        $data = $request->validated();
        $product = Product::query()->findOrFail($data['product_id']);
        $warehouse = Warehouse::query()->findOrFail($data['warehouse_id']);
        $movement = match ($data['type']) {
            'in' => $inventory->in($product, $warehouse, $data['quantity'], $data['reference'] ?? null),
            'out' => $inventory->out($product, $warehouse, $data['quantity'], $data['reference'] ?? null),
            'transfer' => $inventory->transfer(
                $product,
                $warehouse,
                Warehouse::query()->findOrFail($data['destination_warehouse_id']),
                $data['quantity'],
                $data['reference'] ?? null
            ),
        };
        return new StockMovementResource($movement->load(['product', 'warehouse']));
    }
}
