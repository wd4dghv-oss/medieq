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
        Schema::create('charging_charts', function (Blueprint $table) {
            $table->id();
            $table->date('charging_date');
            $table->string('device_name');
            $table->string('device_id');
            $table->string('device_group');
            $table->time('charging_start');
            $table->time('charging_end');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charging_charts');
    }
};
