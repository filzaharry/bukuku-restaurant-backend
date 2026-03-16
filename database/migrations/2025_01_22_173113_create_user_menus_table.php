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
        Schema::create('user_menus', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name', 255); // varchar(255) for name
            $table->enum('level', ['main', 'sub']); // Enum for 'main' or 'sub'
            $table->enum('is_parent', ['yes', 'no']); // Enum for 'yes' or 'no'
            $table->string('url', 255); // varchar(255) for the URL
            $table->string('master', 11); // varchar(11) for master
            $table->string('sort_sub', 11); // varchar(11) for sorting sub-menus
            $table->string('sort_master', 11); // varchar(11) for sorting master menus
            $table->foreignId('icon_id')->constrained('master_icons'); // Foreign key referencing master_icons
            $table->integer('status')->default(0)->comment('0->no ; 1->yes');
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
        Schema::dropIfExists('user_menus');
    }
};