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

        if (! $currentTenant) {
            return $next($request);
        }

        try {
            /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
            $guard = auth('tenant');

            $jwtTenantId = $guard->payload()?->get('tenant_id');

            if ($jwtTenantId && (string) $jwtTenantId !== (string) $currentTenant->id) {
                abort(403, 'Token not valid for this tenant');
            }
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            // No valid JWT present or token invalid - let auth middleware handle it
        }

        return $next($request);
    }
}
