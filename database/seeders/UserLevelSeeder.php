<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('user_levels')->insert([
            ['name' => 'Admin', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'User', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Guest', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
