<?php

declare(strict_types=1);

namespace Tests\Unit\JWT;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class JwtConfigTest extends TestCase
{
    public function test_jwt_secret_is_configured(): void
    {
        $secret = Config::get('jwt.secret');
        
        $this->assertNotNull($secret, 'JWT secret must be configured');
        $this->assertIsString($secret, 'JWT secret must be a string');
        $this->assertNotEmpty($secret, 'JWT secret cannot be empty');
    }

    public function test_jwt_ttl_is_configured(): void
    {
        $ttl = Config::get('jwt.ttl');
        
        $this->assertNotNull($ttl, 'JWT TTL must be configured');
        $this->assertIsInt($ttl, 'JWT TTL must be an integer');
        $this->assertGreaterThan(0, $ttl, 'JWT TTL must be greater than 0');
        $this->assertEquals(60, $ttl, 'JWT TTL should default to 60 minutes');
    }

    public function test_jwt_refresh_ttl_is_configured(): void
    {
        $refreshTtl = Config::get('jwt.refresh_ttl');
        
        $this->assertNotNull($refreshTtl, 'JWT refresh TTL must be configured');
        $this->assertIsInt($refreshTtl, 'JWT refresh TTL must be an integer');
        $this->assertGreaterThan(0, $refreshTtl, 'JWT refresh TTL must be greater than 0');
        $this->assertEquals(20160, $refreshTtl, 'JWT refresh TTL should default to 2 weeks (20160 minutes)');
    }

    public function test_jwt_algorithm_is_configured(): void
    {
        $algo = Config::get('jwt.algo');
        
        $this->assertNotNull($algo, 'JWT algorithm must be configured');
        $this->assertIsString($algo, 'JWT algorithm must be a string');
        $this->assertNotEmpty($algo, 'JWT algorithm cannot be empty');
        $this->assertEquals('HS256', $algo, 'JWT algorithm should default to HS256');
    }

    public function test_jwt_required_claims_are_configured(): void
    {
        $requiredClaims = Config::get('jwt.required_claims');
        
        $this->assertIsArray($requiredClaims, 'JWT required claims must be an array');
        $this->assertNotEmpty($requiredClaims, 'JWT required claims cannot be empty');
        
        $expectedClaims = ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti'];
        foreach ($expectedClaims as $claim) {
            $this->assertContains($claim, $requiredClaims, "Required claim '{$claim}' must be present");
        }
    }

    public function test_jwt_blacklist_is_enabled(): void
    {
        $blacklistEnabled = Config::get('jwt.blacklist_enabled');
        
        $this->assertTrue($blacklistEnabled, 'JWT blacklist should be enabled by default');
    }

    public function test_jwt_providers_are_configured(): void
    {
        $providers = Config::get('jwt.providers');
        
        $this->assertIsArray($providers, 'JWT providers must be an array');
        $this->assertArrayHasKey('jwt', $providers, 'JWT provider must be configured');
        $this->assertArrayHasKey('auth', $providers, 'Auth provider must be configured');
        $this->assertArrayHasKey('storage', $providers, 'Storage provider must be configured');
        
        $this->assertEquals(
            'PHPOpenSourceSaver\\JWTAuth\\Providers\\JWT\\Lcobucci',
            $providers['jwt'],
            'JWT provider should use Lcobucci implementation'
        );
        
        $this->assertEquals(
            'PHPOpenSourceSaver\\JWTAuth\\Providers\\Auth\\Illuminate',
            $providers['auth'],
            'Auth provider should use Laravel implementation'
        );
        
        $this->assertEquals(
            'PHPOpenSourceSaver\\JWTAuth\\Providers\\Storage\\Illuminate',
            $providers['storage'],
            'Storage provider should use Laravel implementation'
        );
    }

    public function test_jwt_lock_subject_is_enabled(): void
    {
        $lockSubject = Config::get('jwt.lock_subject');
        
        $this->assertTrue($lockSubject, 'JWT lock subject should be enabled by default for security');
    }

    public function test_jwt_blacklist_grace_period(): void
    {
        $gracePeriod = Config::get('jwt.blacklist_grace_period');
        
        $this->assertIsInt($gracePeriod, 'JWT blacklist grace period must be an integer');
        $this->assertGreaterThanOrEqual(0, $gracePeriod, 'JWT blacklist grace period must be non-negative');
        $this->assertEquals(0, $gracePeriod, 'JWT blacklist grace period should default to 0');
    }

    public function test_jwt_leeway_configuration(): void
    {
        $leeway = Config::get('jwt.leeway');
        
        $this->assertIsInt($leeway, 'JWT leeway must be an integer');
        $this->assertGreaterThanOrEqual(0, $leeway, 'JWT leeway must be non-negative');
        $this->assertEquals(0, $leeway, 'JWT leeway should default to 0');
    }

    public function test_jwt_refresh_iat_configuration(): void
    {
        $refreshIat = Config::get('jwt.refresh_iat');
        
        $this->assertIsBool($refreshIat, 'JWT refresh iat must be a boolean');
        $this->assertFalse($refreshIat, 'JWT refresh iat should default to false');
    }
}