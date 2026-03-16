<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone');
            $table->unsignedBigInteger('province_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('district_id');
            $table->text('address');
            $table->integer('status')->comment('0 -> unprocess, 1 -> process, 2 -> completed, 3 -> uncompleted');
            $table->string('toren_size')->nullable();
            $table->string('duration')->nullable();
            $table->string('created_by_id', 20)->nullable(); // varchar(20), nullable
            $table->string('updated_by_id', 20)->nullable(); // varchar(20), nullable
            $table->string('deleted_by_id', 20)->nullable(); // varchar(20), nullable
            $table->timestamps();

            $table->foreign('province_id')->references('id')->on('master_province');
            $table->foreign('city_id')->references('id')->on('master_city');
            $table->foreign('district_id')->references('id')->on('master_district');
        });
    }

    public function down()
    {
        Schema::dropIfExists('entries');
    }
};