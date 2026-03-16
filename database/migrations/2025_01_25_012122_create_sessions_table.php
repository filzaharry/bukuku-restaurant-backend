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
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();  // varchar(255) -> sebagai primary key
            $table->unsignedBigInteger('user_id')->nullable();  // bigint unsigned
            $table->string('ip_address', 45)->nullable();  // varchar(45)
            $table->text('user_agent')->nullable();  // text
            $table->longText('payload');  // longtext
            $table->integer('last_activity');  // int

            // Jika ada foreign key constraints, tambahkan seperti berikut (misal user_id refer ke users)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();  // Untuk created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};