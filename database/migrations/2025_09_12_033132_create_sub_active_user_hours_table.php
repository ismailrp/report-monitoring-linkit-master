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
        Schema::create('sub_active_user_hours', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('hour');
            $table->integer('id_operator');
            $table->integer('id_service');
            $table->integer('total_sub');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_active_user_hours');
    }
};
