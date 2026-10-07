<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockLevelResource;
use App\Models\StockLevel;
use Illuminate\Http\Request;

class StockLevelController extends Controller
{
    public function index(Request $request)
    {
        $stockLevels = StockLevel::query()
            ->with([
                'product:id,sku,name',
                'warehouse:id,name',
            ])->when(
                $request->filled('product_id'),
                fn ($query) => $query->where(
                    'product_id',
                    $request->integer('product_id')
                )
            )->when(
                $request->filled('warehouse_id'),
                fn ($query) => $query->where(
                    'warehouse_id',
                    $request->integer('warehouse_id')
                )
            )
            ->orderBy('id')
            ->paginate(20);

        return StockLevelResource::collection($stockLevels);
    }
}
