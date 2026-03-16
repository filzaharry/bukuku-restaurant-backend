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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('username', 255)->unique(); // varchar(255) for username, ensuring it's unique
            $table->string('name', 255); // varchar(255) for user name
            $table->string('email', 255)->unique(); // varchar(255) for email, ensuring it's unique
            $table->integer('status')->default(0)->comment('0->no ; 1->yes');
            $table->string('phone', 255); // varchar(255) for phone number
            $table->timestamp('email_verified_at')->nullable(); // Nullable timestamp for email verification time
            $table->string('password', 255); // varchar(255) for user password
            $table->foreignId('level_id')->constrained('user_levels')->onDelete('cascade'); // Foreign key to 'user_levels' table
            $table->string('remember_token', 100)->nullable(); // Nullable varchar(100) for "remember me" token
            $table->string('created_by_id', 20)->nullable(); // Nullable varchar(20) for created_by_id
            $table->string('updated_by_id', 20)->nullable(); // Nullable varchar(20) for updated_by_id
            $table->string('deleted_by_id', 20)->nullable(); // Nullable varchar(20) for deleted_by_id
            $table->timestamps(0); // Timestamps (created_at, updated_at) with no fractional seconds precision
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
