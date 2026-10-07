<?php

namespace App\Exceptions;

use App\Models\Product;
use App\Models\Warehouse;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly Product $product,
        public readonly Warehouse $warehouse,
        public readonly int $requestedQuantity,
        public readonly int $availableQuantity,
    ) {
        parent::__construct("موجودی محصول کافی نیست.");
    }
}
