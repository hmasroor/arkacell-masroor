<?php

namespace App\View\Composers;

use App\Models\Tenant;
use Illuminate\View\View;

class TenantSwitcherComposer
{
    public function compose(View $view): void
    {
        $tenants = Tenant::query()->orderBy('id')->get();
        $currentTenant = request()->attributes->get('tenant');
        $view->with([
            'tenants' => $tenants,
            'selectedTenantId' => $currentTenant?->id ?? $tenants->first()?->id,
        ]);
    }
}
