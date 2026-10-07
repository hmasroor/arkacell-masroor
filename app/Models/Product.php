<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

#[Fillable(['tenant_id', 'sku', 'name', 'unit_price'])]
#[ScopedBy([TenantScope::class])]
class Product extends Model
{
    use HasFactory, BelongsToTenant;

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class);
    }
}
