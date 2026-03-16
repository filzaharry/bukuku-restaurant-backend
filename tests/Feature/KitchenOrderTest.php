<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KitchenOrderTest extends TestCase
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
            'username' => 'kitchen_user',
            'name' => 'Kitchen User',
            'email' => 'kitchen@example.com',
            'phone' => '123456789',
            'level_id' => 2,
            'status' => 1,
            'password' => bcrypt('password'),
        ]);

        // Generate JWT token
        $this->token = auth('jwt')->tokenById($this->user->id);
    }

    /**
     * Test getting list of orders for kitchen
     */
    public function test_get_kitchen_orders(): void
    {
        // Create sample orders
        $orderId = DB::table('fnb_orders')->insertGetId([
            'table_number' => 'T1',
            'customer_name' => 'John Doe',
            'status' => 'pending',
            'total_amount' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add order items
        DB::table('fnb_order_items')->insert([
            'order_id' => $orderId,
            'menu_id' => 1,
            'quantity' => 2,
            'price' => 25000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/v1/admin/fnb/order');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'statusCode',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'table_number',
                                'customer_name',
                                'status',
                                'total_amount',
                                'items',
                            ],
                        ],
                    ],
                ]);
    }

    /**
     * Test updating order status from pending to preparing
     */
    public function test_update_order_status_from_pending_to_preparing(): void
    {
        // Create a pending order
        $orderId = DB::table('fnb_orders')->insertGetId([
            'table_number' => 'T2',
            'customer_name' => 'Jane Smith',
            'status' => 'pending',
            'total_amount' => 75000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/admin/fnb/order/$orderId/preparing");

        $response->assertStatus(200)
                ->assertJson([
                    'statusCode' => 200,
                ]);

        $this->assertDatabaseHas('fnb_orders', [
            'id' => $orderId,
            'status' => 'preparing',
        ]);
    }

    /**
     * Test updating order status from preparing to ready
     */
    public function test_update_order_status_from_preparing_to_ready(): void
    {
        // Create a preparing order
        $orderId = DB::table('fnb_orders')->insertGetId([
            'table_number' => 'T3',
            'customer_name' => 'Bob Johnson',
            'status' => 'preparing',
            'total_amount' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/v1/admin/fnb/order/$orderId/ready");

        $response->assertStatus(200)
                ->assertJson([
                    'statusCode' => 200,
                ]);

        $this->assertDatabaseHas('fnb_orders', [
            'id' => $orderId,
            'status' => 'ready',
        ]);
    }

    /**
     * Test creating a new order
     */
    public function test_create_new_order(): void
    {
        $orderData = [
            'table_number' => 'T4',
            'customer_name' => 'Alice Brown',
            'items' => [
                [
                    'menu_id' => 1,
                    'quantity' => 2,
                    'price' => 30000,
                ],
                [
                    'menu_id' => 2,
                    'quantity' => 1,
                    'price' => 20000,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/fnb/order', $orderData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'statusCode',
                    'message',
                    'data' => [
                        'id',
                        'table_number',
                        'customer_name',
                        'status',
                        'total_amount',
                    ],
                ]);

        $this->assertDatabaseHas('fnb_orders', [
            'table_number' => 'T4',
            'customer_name' => 'Alice Brown',
            'status' => 'pending',
        ]);
    }

    /**
     * Test unauthorized access to kitchen endpoints
     */
    public function test_unauthorized_access_to_kitchen_endpoints(): void
    {
        $response = $this->getJson('/api/v1/admin/fnb/order');

        $response->assertStatus(401);
    }

    /**
     * Test updating non-existent order status
     */
    public function test_update_nonexistent_order_status(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/admin/fnb/order/999/preparing');

        $response->assertStatus(404);
    }
}
