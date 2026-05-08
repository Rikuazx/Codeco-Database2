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
       Schema::create('class_sessions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('class_id')->constrained()->cascadeOnDelete();
        $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();

        $table->timestamp('start_time');
        $table->timestamp('end_time');
        $table->enum('status', ['scheduled', 'ongoing', 'completed'])
        ->default('scheduled');
        $table->timestamps();

        $table->foreignId('recommended_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
