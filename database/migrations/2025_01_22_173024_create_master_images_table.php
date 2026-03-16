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
        Schema::create('master_images', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (bigint)
            $table->text('image'); // varchar(255) for image (image file name or URL)
            $table->string('created_by_id', 20)->nullable(); // nullable varchar(20)
            $table->string('updated_by_id', 20)->nullable(); // nullable varchar(20)
            $table->string('deleted_by_id', 20)->nullable(); // nullable varchar(20)
            $table->timestamps(0); // Timestamps with no fractional seconds precision
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_images');
    }
};	