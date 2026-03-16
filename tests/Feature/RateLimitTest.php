<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed user levels
        $this->artisan('db:seed', ['--class' => 'UserLevelSeeder']);
    }

    /**
     * Test login endpoint rate limiting
     */
    public function test_login_endpoint_rate_limiting(): void
    {
        $userData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        // First 5 attempts should succeed (with 401 for wrong credentials)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', $userData);
            $this->assertContains($response->status(), [401, 422]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/v1/auth/login', $userData);
        
        $response->assertStatus(429)
                ->assertJson([
                    'statusCode' => 429,
                    'message' => 'Too many attempts. Please try again later.',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'retry_after',
                        'max_attempts',
                    ],
                ]);

        // Check rate limit headers
        $response->assertHeader('X-RateLimit-Limit', '5');
        $response->assertHeader('X-RateLimit-Remaining', '0');
        $response->assertHeader('Retry-After');
        $response->assertHeader('X-RateLimit-Reset');
    }

    /**
     * Test register endpoint rate limiting
     */
    public function test_register_endpoint_rate_limiting(): void
    {
        $userData = [
            'username' => 'testuser' . time(),
            'fullname' => 'Test User',
            'email' => 'test' . time() . '@example.com',
            'phone' => '123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // First attempt should succeed
        $response = $this->postJson('/api/v1/auth/register', $userData);
        $this->assertContains($response->status(), [201, 422]);

        // Modify data for subsequent attempts (same username to trigger validation)
        for ($i = 2; $i <= 3; $i++) {
            $userData['email'] = 'test' . time() . $i . '@example.com';
            $response = $this->postJson('/api/v1/auth/register', $userData);
            $this->assertContains($response->status(), [201, 422]);
        }

        // 4th attempt should be rate limited
        $userData['email'] = 'test' . time() . '4@example.com';
        $response = $this->postJson('/api/v1/auth/register', $userData);
        
        $response->assertStatus(429)
                ->assertJson([
                    'statusCode' => 429,
                    'message' => 'Too many attempts. Please try again later.',
                ]);

        // Check rate limit headers
        $response->assertHeader('X-RateLimit-Limit', '3');
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }

    /**
     * Test forgot password endpoint rate limiting
     */
    public function test_forgot_password_endpoint_rate_limiting(): void
    {
        $emailData = [
            'email' => 'test@example.com',
        ];

        // First 3 attempts should get validation error (email doesn't exist)
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/v1/auth/forgot-password', $emailData);
            $this->assertContains($response->status(), [422, 200]);
        }

        // 4th attempt should be rate limited
        $response = $this->postJson('/api/v1/auth/forgot-password', $emailData);
        
        $response->assertStatus(429)
                ->assertJson([
                    'statusCode' => 429,
                    'message' => 'Too many attempts. Please try again later.',
                ]);
    }

    /**
     * Test verify OTP endpoint rate limiting
     */
    public function test_verify_otp_endpoint_rate_limiting(): void
    {
        $otpData = [
            'email' => 'test@example.com',
            'otp' => '123456',
            'purpose' => 'login',
        ];

        // First 10 attempts should get validation error (OTP doesn't exist)
        for ($i = 1; $i <= 10; $i++) {
            $response = $this->postJson('/api/v1/auth/verify-otp', $otpData);
            $this->assertContains($response->status(), [401, 422]);
        }

        // 11th attempt should be rate limited
        $response = $this->postJson('/api/v1/auth/verify-otp', $otpData);
        
        $response->assertStatus(429)
                ->assertJson([
                    'statusCode' => 429,
                    'message' => 'Too many attempts. Please try again later.',
                ]);
    }

    /**
     * Test rate limiting headers are present on successful requests
     */
    public function test_rate_limit_headers_on_successful_request(): void
    {
        // Create a user first
        $user = User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'level_id' => 2,
            'status' => 1,
            'password' => bcrypt('password'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        // Should have rate limit headers
        $response->assertHeader('X-RateLimit-Limit', '5');
        $response->assertHeader('X-RateLimit-Remaining');
    }
}
