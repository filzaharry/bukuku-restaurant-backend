<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * Test user registration with valid data
     */
    public function test_user_registration_with_valid_data(): void
    {
        // Use unique data per run to avoid collision in persistent database
        $uniqueSuffix = time() . '_' . rand(100, 999);
        $userData = [
            'username' => 'user_' . $uniqueSuffix,
            'fullname' => 'Test User ' . $uniqueSuffix,
            'email' => 'test_' . $uniqueSuffix . '@example.com',
            'phone' => '0812' . rand(10000000, 99999999),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'username' => $userData['username'],
        ]);
    }
}
