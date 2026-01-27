<?php

declare(strict_types=1);

namespace Tests\Feature\JWT;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\CompanyManagement\Models\CompanyUser;
use Modules\GlobalAdmin\Models\Admin;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landlord_authentication_with_valid_credentials(): void
    {
        $admin = Admin::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/landlord/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);

        $this->assertArrayHasKey('access_token', $response->json());
    }

    public function test_tenant_authentication_with_valid_credentials(): void
    {
        $tenant = $this->createAndActAsTenant();

        $companyUser = CompanyUser::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/tenant/login', [
            'email' => $companyUser->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_api_authentication_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_landlord_authentication_with_invalid_credentials(): void
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->postJson('/api/auth/landlord/login', [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_tenant_authentication_with_invalid_credentials(): void
    {
        $this->createAndActAsTenant();

        CompanyUser::factory()->create([
            'email' => 'user@company.com',
        ]);

        $response = $this->postJson('/api/auth/tenant/login', [
            'email' => 'user@company.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_protected_route_with_valid_landlord_token(): void
    {
        $admin = Admin::factory()->create();
        $token = auth('landlord')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(200)
            ->assertJson(['user' => $admin->email]);
    }

    public function test_protected_route_with_valid_tenant_token(): void
    {
        $tenant = $this->createAndActAsTenant();
        $companyUser = CompanyUser::factory()->create();

        $token = auth('tenant')->claims(['tenant_id' => $tenant->id])->login($companyUser);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/tenant/protected');

        $response->assertStatus(200)
            ->assertJson(['user' => $companyUser->email]);
    }

    public function test_protected_route_without_token(): void
    {
        $response = $this->getJson('/api/landlord/protected');

        $response->assertStatus(401);
    }

    public function test_protected_route_with_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid.token.here',
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(401);
    }

    public function test_token_contains_tenant_id_for_tenant_authentication(): void
    {
        $tenant = $this->createAndActAsTenant();
        $companyUser = CompanyUser::factory()->create();

        $token = auth('tenant')->claims(['tenant_id' => $tenant->id])->login($companyUser);
        $payload = JWTAuth::setToken($token)->getPayload();

        $this->assertEquals($tenant->id, $payload->get('tenant_id'));
    }

    public function test_token_refresh_works_correctly(): void
    {
        $admin = Admin::factory()->create();
        $token = auth('landlord')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);

        $newToken = $response->json('access_token');
        $this->assertNotEquals($token, $newToken);
    }

    public function test_logout_invalidates_token(): void
    {
        $admin = Admin::factory()->create();
        $token = auth('landlord')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(401);
    }

    public function test_current_user_endpoint_returns_authenticated_user(): void
    {
        $admin = Admin::factory()->create();
        $token = auth('landlord')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson(['id' => $admin->id, 'email' => $admin->email]);
    }

    public function test_tenant_context_is_set_from_jwt_token(): void
    {
        $tenant = $this->createAndActAsTenant();
        $companyUser = CompanyUser::factory()->create();

        $token = auth('tenant')->claims(['tenant_id' => $tenant->id])->login($companyUser);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token);

        JWTAuth::setRequest($request)->parseToken();
        $payload = JWTAuth::getPayload();
        $tenantId = $payload->get('tenant_id');

        $this->assertEquals($tenant->id, $tenantId);
    }

    public function test_middleware_extracts_tenant_id_correctly(): void
    {
        $tenant = $this->createAndActAsTenant();
        $companyUser = CompanyUser::factory()->create();

        $token = auth('tenant')->claims(['tenant_id' => $tenant->id])->login($companyUser);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/tenant/check-context');

        $response->assertStatus(200)
            ->assertJson(['tenant_id' => $tenant->id]);
    }

    public function test_authentication_with_expired_token(): void
    {
        $admin = \Modules\GlobalAdmin\Models\Admin::factory()->create();

        // Expire token by setting time back, creating it, then moving forward
        \Illuminate\Support\Carbon::setTestNow(now()->subHours(2));
        $expiredToken = auth('landlord')->fromUser($admin);
        \Illuminate\Support\Carbon::setTestNow();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$expiredToken,
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(401);
    }

    public function test_guard_isolation_between_different_authentication_types(): void
    {
        $admin = \Modules\GlobalAdmin\Models\Admin::factory()->create();
        $user = \App\Models\User::factory()->create();

        $adminToken = auth('landlord')->fromUser($admin);
        $userToken = auth('api')->fromUser($user);
        
        // Ensure tokens have the 'prv' claim for isolation
        $adminPayload = auth('landlord')->setToken($adminToken)->getPayload();
        $userPayload = auth('api')->setToken($userToken)->getPayload();
        
        $this->assertEquals(sha1(\Modules\GlobalAdmin\Models\Admin::class), $adminPayload->get('prv'), 'Admin token missing/wrong prv claim');
        $this->assertEquals(sha1(\App\Models\User::class), $userPayload->get('prv'), 'User token missing/wrong prv claim');

        // 1. Admin token should work for landlord routes
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$adminToken,
        ])->getJson('/api/landlord/protected');
        $response->assertStatus(200);

        // 2. Admin token should NOT work for api (User) routes
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$adminToken,
        ])->getJson('/api/user/protected');
        $response->assertStatus(401);

        // 3. User token should NOT work for landlord routes
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$userToken,
        ])->getJson('/api/landlord/protected');
        $response->assertStatus(401);

        // 4. User token should work for api (User) routes
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$userToken,
        ])->getJson('/api/user/protected');
        $response->assertStatus(200);
    }
}
