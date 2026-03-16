<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('content_service', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle');
            $table->text('image');
            $table->integer('status')->default(0);
            $table->string('created_by_id', 20)->nullable();
            $table->string('updated_by_id', 20)->nullable();
            $table->string('deleted_by_id', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_service');
    }
};