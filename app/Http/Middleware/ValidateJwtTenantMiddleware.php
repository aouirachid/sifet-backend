<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\GlobalAdmin\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class ValidateJwtTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentTenant = Tenant::current();

        // If no tenant is resolved (e.g. landlord or public route), we might skip
        // or if this middleware is strictly for tenant routes, we expect a tenant.
        if (! $currentTenant) {
            return $next($request);
        }

        // User requested to rely on TenantFinder (Domain) resolution,
        // effectively trusting that if the user exists in the resolved DB, they are valid.
        // Removed explicit tenant_id claim check.

        return $next($request);
    }
}
