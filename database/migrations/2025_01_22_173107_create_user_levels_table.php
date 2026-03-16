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
        Schema::create('user_levels', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (bigserial in PostgreSQL)
            $table->string('name', 255); // varchar(255) for name
            $table->integer('status')->default(0)->comment('0->no ; 1->yes');
            $table->string('created_by_id', 20)->nullable(); // Nullable varchar(20) for created_by_id
            $table->string('updated_by_id', 20)->nullable(); // Nullable varchar(20) for updated_by_id
            $table->string('deleted_by_id', 20)->nullable(); // Nullable varchar(20) for deleted_by_id
            $table->timestamps(0); // Timestamps with no fractional seconds precision
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_levels');
    }
};
