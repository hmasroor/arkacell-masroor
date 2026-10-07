<?php

namespace App\Traits;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model): void {
            $tenantId = request()->attributes->get('tenant')?->id;
            if (empty($tenantId) && request()->hasSession()) {
                $tenantId = request()->session()->get('tenant_id');
            }

            if (empty($tenantId)) {
                throw new \RuntimeException('Tenant not found');
            }

            $model->tenant_id = $tenantId;
        });
    }
}
