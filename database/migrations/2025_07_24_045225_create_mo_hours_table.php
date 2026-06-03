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
        Schema::create('mo_hours', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('hour');
            $table->integer('id_operator');
            $table->string('operator');
            $table->integer('id_service');
            $table->string('service');
            $table->integer('total_reg');
            $table->integer('total_unreg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mo_hours');
    }
};
