<?php

declare(strict_types=1);

namespace App\TenantFinder;

use Illuminate\Http\Request;
use Modules\GlobalAdmin\Models\Domain;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    // Overide
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = $request->getHost();

        $domain = Domain::where('domain', $host)->first();

        return $domain ? $domain->tenant : null;
    }
}
