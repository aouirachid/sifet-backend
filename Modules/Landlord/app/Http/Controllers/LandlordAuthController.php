<?php

declare(strict_types=1);

namespace Modules\Landlord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Landlord\Actions\LandlordLoginAction;
use Modules\Landlord\Http\Requests\LoginRequest;

class LandlordAuthController extends Controller
{
    /**
     * Handle landlord login: validate request, run action, return token + user or 401.
     * Route uses throttle:5,1 (5 attempts per minute) — configured in routes.
     */
    public function login(LoginRequest $request, LandlordLoginAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if ($result === null) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json($result, 200);
    }
}
