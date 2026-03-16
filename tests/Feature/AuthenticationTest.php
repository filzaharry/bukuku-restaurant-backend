<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed user levels
        $this->artisan('db:seed', ['--class' => 'UserLevelSeeder']);
    }

    /**
     * Test user registration with valid data
     */
    public function test_user_registration_with_valid_data(): void
    {
        $userData = [
            'username' => 'testuser123',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJson([
                    'statusCode' => 201,
                    'message' => 'Registrasi berhasil. Silakan login.',
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'testuser123',
        ]);
    }

    /**
     * Test user login with valid credentials and OTP flow
     */
    public function test_user_login_with_valid_credentials_sends_otp(): void
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

        Mail::fake();

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJson([
                    'statusCode' => 200,
                    'message' => 'Kode OTP telah dikirim ke email kamu. Silakan verifikasi untuk melanjutkan.',
                    'data' => [
                        'next_step' => 'verify_otp',
                    ],
                ]);

        // Check if OTP was created in database
        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
            'purpose' => 'login',
            'verified' => false,
        ]);

        // Check if email was sent
        Mail::assertSent(\App\Mail\PasswordResetOtpMail::class);
    }

    /**
     * Test OTP verification for login
     */
    public function test_otp_verification_for_login_returns_jwt_token(): void
    {
        // Create a user
        $user = User::create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'level_id' => 2,
            'status' => 1,
            'password' => bcrypt('password'),
        ]);

        // Create OTP record manually
        $otp = '123456';
        DB::table('password_resets')->insert([
            'email' => 'test@example.com',
            'otp' => $otp,
            'purpose' => 'login',
            'verified' => false,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otpData = [
            'email' => 'test@example.com',
            'otp' => $otp,
            'purpose' => 'login',
        ];

        $response = $this->postJson('/api/v1/auth/verify-otp', $otpData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'statusCode',
                    'message',
                    'data' => [
                        'token',
                        'token_type',
                        'expires_in',
                        'user',
                    ],
                ]);

        // Verify JWT token is returned
        $this->assertNotEmpty($response->json('data.token'));
        
        // Verify OTP is deleted after successful verification
        $this->assertDatabaseMissing('password_resets', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test login with invalid credentials
     */
    public function test_login_with_invalid_credentials(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(401)
                ->assertJson([
                    'statusCode' => 401,
                ]);
    }

    /**
     * Test OTP verification with invalid OTP
     */
    public function test_otp_verification_with_invalid_otp(): void
    {
        $otpData = [
            'email' => 'test@example.com',
            'otp' => '999999',
            'purpose' => 'login',
        ];

        $response = $this->postJson('/api/v1/auth/verify-otp', $otpData);

        // Should return 401 because OTP doesn't exist, or 422 for validation
        $this->assertContains($response->status(), [401, 422]);
    }
}
