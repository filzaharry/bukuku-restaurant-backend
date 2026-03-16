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
        Schema::create('master_params', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (bigint)
            $table->string('param'); // varchar(255) for param
            $table->string('value'); // varchar(255) for value
            $table->text('description'); // text for description
            $table->integer('status')->default(0)->comment('0->no ; 1->yes');
            $table->string('created_by_id', 20)->nullable(); // nullable varchar(20)
            $table->string('updated_by_id', 20)->nullable(); // nullable varchar(20)
            $table->string('deleted_by_id', 20)->nullable(); // nullable varchar(20)
            $table->string('lang'); // varchar(255) for lang
            $table->timestamps(0); // Timestamps with no fractional seconds precision
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_params');
    }
};
