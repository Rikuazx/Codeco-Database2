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
      Schema::create('teacher_availabilities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('teacher_id')->constrained()->onDelete('cascade');

    //  2-week period
    $table->date('period_start');
    $table->date('period_end');

    //  availability type
    $table->enum('type', ['time_range', 'full_day', 'unavailable']);

    //  optional time range
    $table->time('start_time')->nullable();
    $table->time('end_time')->nullable();

    $table->timestamp('submitted_at')->nullable();

    $table->timestamps();
    /* Temporary fix: add date column for easier querying, will be removed in future refactor */
      Schema::table('teacher_availabilities', function (Blueprint $table) {
            $table->date('date')->after('teacher_id');
        });
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::table('teacher_availabilities', function (Blueprint $table) {
            $table->dropColumn('date');
        });
          Schema::dropIfExists('availabilities');
    }
};
