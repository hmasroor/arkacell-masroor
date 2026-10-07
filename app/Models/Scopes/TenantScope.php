<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = request()->attributes->get('tenant')?->id;
        if (empty($tenant) && request()->hasSession()) {
            $tenant = request()->session()->get('tenant_id');
        }

        if (empty($tenant)) {
            throw new \RuntimeException('Tenant not found');
        }

        $builder->where($model->qualifyColumn('tenant_id'), $tenant);
    }
}
