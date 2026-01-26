<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\GlobalAdmin\Models\Tenant;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class TenancyByJwtToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        // 1. Tentative de résolution via JWT
        try {
            if ($token = JWTAuth::getToken()) {
                $payload = JWTAuth::setToken($token)->getPayload();
                $tenantId = $payload->get('tenant_id');
                if ($tenantId) {
                    $tenant = Tenant::find($tenantId);
                }
            }
        } catch (JWTException $e) {
            // Token invalide ou expiré, on continue pour tenter la résolution par domaine
        } catch (\Exception $e) {
            Log::warning('Erreur lors de la résolution du tenant via JWT', ['error' => $e->getMessage()]);
        }

        // 2. Si non trouvé via JWT, tentative via le domaine (DomainTenantFinder)
        if (! $tenant) {
            $tenantFinderClass = config('multitenancy.tenant_finder');
            if ($tenantFinderClass) {
                $tenant = app($tenantFinderClass)->findForRequest($request);
            }
        }

        // 3. Activation du tenant si trouvé
        if ($tenant) {
            $tenant->makeCurrent();
        }

        return $next($request);
    }
}
