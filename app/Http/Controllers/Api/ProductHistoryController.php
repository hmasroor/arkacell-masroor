<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductHistoryController extends Controller
{
    public function index(Request $request, string $sku)
    {
        $product = Product::query()
            ->where('sku', $sku)
            ->firstOrFail();

        $movements = $product->stockMovements()
            ->with(['product', 'warehouse'])
            ->latest('created_at')
            ->paginate(20);

        return StockMovementResource::collection($movements);
    }
}
