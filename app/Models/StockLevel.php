<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

#[Fillable(['tenant_id', 'product_id', 'warehouse_id', 'quantity'])]
#[ScopedBy([TenantScope::class])]
class StockLevel extends Model
{
    use BelongsToTenant;

    public const CREATED_AT = null;

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
}
