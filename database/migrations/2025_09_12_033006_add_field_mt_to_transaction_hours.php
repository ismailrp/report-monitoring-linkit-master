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
        Schema::table('transaction_hours', function (Blueprint $table) {
            $table->integer('mt_success')->nuluable()->after('service');
            $table->integer('mt_failed')->nuluable()->after('mt_success');
            $table->integer('total_mt')->nullable()->after('mt_failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_hours', function (Blueprint $table) {
            //
        });
    }
};
