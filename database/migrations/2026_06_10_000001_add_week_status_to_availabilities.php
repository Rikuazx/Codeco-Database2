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
        Schema::table('availabilities', function (Blueprint $table) {
            $table->tinyInteger('week_number')->default(1)->after('period_end'); // 1 = minggu pertama, 2 = minggu kedua
            $table->enum('week_status', ['confirmed', 'tentative'])->default('confirmed')->after('week_number');
            $table->boolean('is_locked')->default(false)->after('week_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropColumn(['week_number', 'week_status', 'is_locked']);
        });
    }
};
