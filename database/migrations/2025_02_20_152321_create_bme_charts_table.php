<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
  
    {
        Schema::create('bme_charts', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('device_id');
            $table->string('device_group');
            $table->string('reason');
            $table->date('send_date');
            $table->date('receive_date')->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('bme_charts');
    }
};
