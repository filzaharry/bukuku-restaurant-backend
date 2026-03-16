<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_icons', function (Blueprint $table) {
            $table->id(); // auto-increment id (bigint in PostgreSQL)
            $table->string('name'); // varchar(255) for 'name'
            $table->string('code'); // varchar(255) for 'code'
            $table->boolean('status'); // status as a boolean (true/false, replaces int(1) in MySQL)
            $table->string('created_by_id', 20)->nullable(); // nullable varchar(20)
            $table->string('updated_by_id', 20)->nullable(); // nullable varchar(20)
            $table->string('deleted_by_id', 20)->nullable(); // nullable varchar(20)
            $table->timestamps(0); // created_at and updated_at with no fractional seconds (0 for precision)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_icons');
    }
};