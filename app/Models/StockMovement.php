<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

#[Fillable(['tenant_id', 'product_id', 'warehouse_id', 'type', 'quantity', 'reference', 'meta'])]
#[ScopedBy([TenantScope::class])]
class StockMovement extends Model
{
    use BelongsToTenant;

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
