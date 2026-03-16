<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FnbMenuTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed user levels
        $this->artisan('db:seed', ['--class' => 'UserLevelSeeder']);
        
        // Create master_icons table and data
        DB::statement('CREATE TABLE IF NOT EXISTS master_icons (id INTEGER PRIMARY KEY, name VARCHAR(255), code VARCHAR(255), status INTEGER DEFAULT 1, created_at DATETIME, updated_at DATETIME)');
        DB::table('master_icons')->insert([
            'name' => 'Default Icon',
            'code' => 'default_icon',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create fnb_menus table
        DB::statement('CREATE TABLE IF NOT EXISTS fnb_menus (id INTEGER PRIMARY KEY, name VARCHAR(255), description TEXT, price INTEGER, category_id INTEGER, status INTEGER DEFAULT 1, created_at DATETIME, updated_at DATETIME)');
        
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
     * Test creating a new F&B menu item
     */
    public function test_create_fnb_menu_item(): void
    {
        // Create a category first
        $categoryId = DB::table('user_menus')->insertGetId([
            'name' => 'Makanan',
            'level' => 'main',
            'is_parent' => 'yes',
            'url' => '/food',
            'master' => '1',
            'sort_sub' => '1',
            'sort_master' => '1',
            'icon_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $menuData = [
            'name' => 'Nasi Goreng Special',
            'description' => 'Nasi goreng dengan telur mata sapi, ayam, dan udang',
            'price' => 25000,
            'category_id' => $categoryId,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/fnb/menu', $menuData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'statusCode',
                    'message',
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'category_id',
                    ],
                ]);

        $this->assertDatabaseHas('fnb_menus', [
            'name' => 'Nasi Goreng Special',
            'price' => 25000,
        ]);
    }

    /**
     * Test getting list of F&B menu items
     */
    public function test_get_fnb_menu_list(): void
    {
        // Create a menu item first
        $categoryId = DB::table('user_menus')->insertGetId([
            'name' => 'Makanan',
            'level' => 'main',
            'is_parent' => 'yes',
            'url' => '/food',
            'master' => '1',
            'sort_sub' => '1',
            'sort_master' => '1',
            'icon_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fnb_menus')->insert([
            'name' => 'Ayam Bakar',
            'description' => 'Ayam bakar dengan sambal',
            'price' => 30000,
            'category_id' => $categoryId,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/admin/fnb/menu');

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

    /**
     * Test updating an F&B menu item
     */
    public function test_update_fnb_menu_item(): void
    {
        // Create a menu item first
        $categoryId = DB::table('user_menus')->insertGetId([
            'name' => 'Makanan',
            'level' => 'main',
            'is_parent' => 'yes',
            'url' => '/food',
            'master' => '1',
            'sort_sub' => '1',
            'sort_master' => '1',
            'icon_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $menuId = DB::table('fnb_menus')->insertGetId([
            'name' => 'Nasi Goreng',
            'description' => 'Nasi goreng biasa',
            'price' => 20000,
            'category_id' => $categoryId,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updateData = [
            'name' => 'Nasi Goreng Spesial',
            'description' => 'Nasi goreng dengan telur dan ayam',
            'price' => 25000,
            'category_id' => $categoryId,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/admin/fnb/menu/$menuId", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'statusCode' => 200,
                ]);

        $this->assertDatabaseHas('fnb_menus', [
            'id' => $menuId,
            'name' => 'Nasi Goreng Spesial',
            'price' => 25000,
        ]);
    }

    /**
     * Test deleting an F&B menu item
     */
    public function test_delete_fnb_menu_item(): void
    {
        // Create a menu item first
        $categoryId = DB::table('user_menus')->insertGetId([
            'name' => 'Makanan',
            'level' => 'main',
            'is_parent' => 'yes',
            'url' => '/food',
            'master' => '1',
            'sort_sub' => '1',
            'sort_master' => '1',
            'icon_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $menuId = DB::table('fnb_menus')->insertGetId([
            'name' => 'Mie Ayam',
            'description' => 'Mie ayam dengan pangsit',
            'price' => 18000,
            'category_id' => $categoryId,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/v1/admin/fnb/menu/$menuId");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('fnb_menus', [
            'id' => $menuId,
        ]);
    }

    /**
     * Test unauthorized access to F&B menu endpoints
     */
    public function test_unauthorized_access_to_fnb_menu(): void
    {
        $response = $this->getJson('/api/v1/admin/fnb/menu');

        // Should return 401 (unauthorized) or 404 (route not found without auth)
        $this->assertContains($response->status(), [401, 404]);
    }
}
