<?php

declare(strict_types=1);

namespace Modules\GlobalAdmin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
// use Modules\GlobalAdmin\Database\Factories\TenantFactory;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $database_name
 * @property array $data
 */
class Tenant extends BaseTenant
{
    use HasFactory, UsesLandlordConnection;

    /**
     * The attributes that are mass assignable.
     */
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'data',
        'database_name',
    ];

    protected $casts = [
        'data' => 'array',
    ];
    // protected static function newFactory(): TenantFactory
    // {
    //     // return TenantFactory::new();
    // }

    public function getDatabaseName(): string
    {
        return $this->database_name;
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }
}
