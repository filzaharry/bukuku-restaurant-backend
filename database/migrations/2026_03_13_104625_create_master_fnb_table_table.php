<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_fnb_table', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('name');
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->unsignedBigInteger('deleted_by_id')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        // Add 10 initial tables
        for ($i = 1; $i <= 10; $i++) {
            DB::table('master_fnb_table')->insert([
                'unique_id' => 'TBL-' . strtoupper(Str::random(6)),
                'name' => 'Table ' . $i,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_fnb_table');
    }
};
