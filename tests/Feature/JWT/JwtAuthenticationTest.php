<?php

declare(strict_types=1);

namespace Tests\Feature\JWT;

use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Modules\GlobalAdmin\Models\Admin;
use Modules\GlobalAdmin\Models\Tenant;
use Modules\CompanyManagement\Models\CompanyUser;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('config:cache');
    }

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
                'expires_in'
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
                'expires_in'
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
                'expires_in'
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
        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(200)
            ->assertJson(['user' => $admin->email]);
    }

    public function test_protected_route_with_valid_tenant_token(): void
    {
        $tenant = $this->createAndActAsTenant();
        $companyUser = CompanyUser::factory()->create();
        
        $token = JWTAuth::fromUser($companyUser, ['tenant_id' => $tenant->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
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
        
        $token = JWTAuth::fromUser($companyUser, ['tenant_id' => $tenant->id]);
        $payload = JWTAuth::setToken($token)->getPayload();

        $this->assertEquals($tenant->id, $payload->get('tenant_id'));
    }

    public function test_token_refresh_works_correctly(): void
    {
        $admin = Admin::factory()->create();
        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);

        $newToken = $response->json('access_token');
        $this->assertNotEquals($token, $newToken);
    }

    public function test_logout_invalidates_token(): void
    {
        $admin = Admin::factory()->create();
        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(401);
    }

    public function test_current_user_endpoint_returns_authenticated_user(): void
    {
        $admin = Admin::factory()->create();
        $token = JWTAuth::fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson(['id' => $admin->id, 'email' => $admin->email]);
    }

    public function test_tenant_context_is_set_from_jwt_token(): void
    {
        $tenant = $this->createAndActAsTenant();
        $companyUser = CompanyUser::factory()->create();
        
        $token = JWTAuth::fromUser($companyUser, ['tenant_id' => $tenant->id]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        JWTAuth::setRequest($request)->parseToken();
        $payload = JWTAuth::getPayload();
        $tenantId = $payload->get('tenant_id');

        $this->assertEquals($tenant->id, $tenantId);
    }

    public function test_middleware_extracts_tenant_id_correctly(): void
    {
        $tenant = $this->createAndActAsTenant();
        $companyUser = CompanyUser::factory()->create();
        
        $token = JWTAuth::fromUser($companyUser, ['tenant_id' => $tenant->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/tenant/check-context');

        $response->assertStatus(200)
            ->assertJson(['tenant_id' => $tenant->id]);
    }

    public function test_authentication_with_expired_token(): void
    {
        $admin = Admin::factory()->create();
        
        $expiredToken = JWTAuth::customClaims(['exp' => time() - 3600])->fromUser($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $expiredToken,
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(401);
    }

    public function test_guard_isolation_between_different_authentication_types(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        
        $adminToken = JWTAuth::fromUser($admin);
        $userToken = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/landlord/protected');

        $response->assertStatus(200);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $adminToken,
        ])->getJson('/api/user/protected');

        $response->assertStatus(401);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
        ])->getJson('/api/user/protected');

        $response->assertStatus(200);
    }
}