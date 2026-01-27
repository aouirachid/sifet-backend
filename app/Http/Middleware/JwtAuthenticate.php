<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        // Force the use of the specified guard
        auth()->shouldUse($guard);

        try {
            // Parse and authenticate the token
            /** @phpstan-ignore-next-line */
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Verify the token's 'prv' claim matches the guard's provider model
            /** @phpstan-ignore-next-line */
            $payload = JWTAuth::getPayload();
            $tokenPrv = $payload->get('prv');

            // Get the expected 'prv' value for this guard
            $provider = config("auth.guards.{$guard}.provider");
            $model = config("auth.providers.{$provider}.model");
            $expectedPrv = sha1($model);

            // Ensure the token was issued for this guard's model
            if ($tokenPrv !== $expectedPrv) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

        } catch (JWTException $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
