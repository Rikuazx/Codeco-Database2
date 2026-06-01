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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');

            // date column for easier querying
            $table->date('date')->after('teacher_id');

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('availabilities');
    }
};
