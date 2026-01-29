<?php

declare(strict_types=1);

namespace Modules\CompanyManagement\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\GlobalAdmin\Models\Tenant;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class CompanyUser extends Authenticatable implements JWTSubject
{
    use HasUuids;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'tenant_id' => Tenant::current()?->id,
        ];
    }
}
