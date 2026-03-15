<?php

declare(strict_types=1);

namespace Modules\Landlord\Actions;

use Illuminate\Support\Facades\Auth;
use Modules\Landlord\Http\Resources\LandlordUserResource;
use Modules\Landlord\Models\LandlordUser;

class LandlordLoginAction
{
    /**
     * Attempt to authenticate a landlord user and return token + user resource.
     * Uses the landlord guard (JWT) and landlord_users table.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{token: string, user: LandlordUserResource}|null Null when credentials are invalid
     */
    public function execute(array $credentials): ?array
    {
        // Attempt auth using the landlord guard; JWT guard returns the token string on success
        $token = Auth::guard('landlord')->attempt($credentials);

        if ($token === false) {
            return null;
        }

        /** @var LandlordUser $user */
        $user = Auth::guard('landlord')->user();

        return [
            'token' => (string) $token,
            'user' => LandlordUserResource::make($user),
        ];
    }
}
