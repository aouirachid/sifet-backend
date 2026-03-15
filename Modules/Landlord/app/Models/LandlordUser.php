<?php

declare(strict_types=1);

namespace Modules\Landlord\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Landlord\Database\Factories\LandlordUserFactory;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

/**
 * @property string $id
 * @property string $full_name
 * @property string $email
 * @property string $password
 */
class LandlordUser extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids, UsesLandlordConnection;

    /**
     * The table associated with the model (landlord DB).
     *
     * @var string
     */
    protected $table = 'landlord_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['full_name', 'email', 'password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array of custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): LandlordUserFactory
    {
        return LandlordUserFactory::new();
    }
}
