<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Content\FnbMenu;
use App\Models\Content\FnbCategory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FnbMenuTest extends TestCase
{
    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Find existing test user or create if not exists
        $this->user = User::where('email', 'test@example.com')->first();
        if (!$this->user) {
            $this->user = User::create([
                'username' => 'testuser',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '123456789',
                'level_id' => 2,
                'status' => 1,
                'password' => bcrypt('password'),
            ]);
        }

        // Generate JWT token (use 'jwt' guard since your routes use it)
        $this->token = auth('jwt')->tokenById($this->user->id);
    }

    public function test_get_fnb_menu_list_unauthorized_fails(): void
    {
        // Calling without token
        $response = $this->getJson('/api/v1/fnb/menu');

        // Should return 401 (unauthorized)
        $response->assertStatus(401);
    }

    public function test_get_fnb_menu_list_authorized_success(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/fnb/menu');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'statusCode',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'name',
                                'description',
                                'price',
                                'category_id',
                            ],
                        ],
                    ],
                ]);
    }
}
