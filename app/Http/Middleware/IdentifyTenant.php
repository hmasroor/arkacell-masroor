<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->findTenant($request);
        abort_if($tenant === null, 404, 'Tenant not found');
        $request->attributes->set('tenant', $tenant);
        if ($request->hasSession()) {
            $request->session()->put('tenant_id', $tenant->id);
        }

        return $next($request);
    }

    private function findTenant(Request $request): ?Tenant
    {
        // Subdomain
        $host = $request->getHost();
        $subdomain = str($host)->before('.')->toString();
        $tenant = Tenant::query()->where('subdomain', $subdomain)->first();
        if (!empty($tenant)) {
            return $tenant;
        }

        // Session for test web
        if ($request->hasSession()) {
            $tenant = $request->session()->get('tenant_id');
            if (!empty($tenant)) {
                return Tenant::query()->find($tenant);
            } else {
                return Tenant::query()->first();
            }
        }

        // API Header
        $tenant = $request->header('X-Tenant');
        if (!empty($tenant)) {
            if (is_numeric($tenant)) {
                return Tenant::query()->find($tenant);
            } else {
                return Tenant::query()->where('subdomain', $tenant)->first();
            }
        }

        return null;
    }
}
