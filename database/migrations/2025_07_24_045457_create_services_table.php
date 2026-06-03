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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_operator')->references('id')->on('operators')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('id_country')->references('id')->on('countries')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('service');
            $table->integer('sdc');
            $table->integer('price');
            $table->string('type');
        });

        // {"id_service":"1331","operator":"waki-rbt-isat","description":"","service_type":"2","sdc":"99876","price":"0.00","keyword":"53034778 1000","owner":"1","keyword_complete":"REG 53034778 1000"}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
