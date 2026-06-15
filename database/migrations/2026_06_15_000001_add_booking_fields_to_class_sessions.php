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
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->boolean('is_open_for_booking')->default(false)->after('status');
            $table->timestamp('booked_at')->nullable()->after('is_open_for_booking');
            $table->foreignId('booked_by_teacher_id')->nullable()->after('booked_at')
                  ->constrained('teachers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['booked_by_teacher_id']);
            $table->dropColumn(['is_open_for_booking', 'booked_at', 'booked_by_teacher_id']);
        });
    }
};
