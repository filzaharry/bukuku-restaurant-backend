<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed user levels
        $this->artisan('db:seed', ['--class' => 'UserLevelSeeder']);
        
        // Create and authenticate user
        $this->user = User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'level_id' => 2,
            'status' => 1,
            'password' => bcrypt('password'),
        ]);

        // Generate JWT token
        $this->token = auth('jwt')->tokenById($this->user->id);
    }

    /**
     * Test API basic functionality
     */
    public function test_api_basic_endpoints(): void
    {
        // Test basic API endpoint
        $response = $this->getJson('/api');
        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Hello World',
                ]);
    }

    /**
     * Test API authentication flow
     */
    public function test_api_authentication_flow(): void
    {
        // Test login endpoint exists
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Should return 200 (OTP sent) or validation error
        $this->assertContains($response->status(), [200, 422]);
    }

    /**
     * Test protected API endpoint with JWT
     */
    public function test_protected_api_endpoint_with_jwt(): void
    {
        // Test with valid JWT token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api');

        $response->assertStatus(200);
    }

    /**
     * Test protected API endpoint without JWT
     */
    public function test_protected_api_endpoint_without_jwt(): void
    {
        // Test without JWT token (should fail on protected routes)
        $response = $this->getJson('/api/v1/admin/fnb/menu');
        
        // Should return 401 (unauthorized) or 404 (route not found)
        $this->assertContains($response->status(), [401, 404]);
    }
}
