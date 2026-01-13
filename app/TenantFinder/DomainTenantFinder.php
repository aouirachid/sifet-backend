<?php

namespace App\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Modules\GlobalAdmin\Models\Domain;

class DomainTenantFinder extends TenantFinder
{
    //Overide
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = $request->getHost();

        $domain = Domain::where('domain', $host)->first();

        return $domain ? $domain->tenant : null;
    }
}
