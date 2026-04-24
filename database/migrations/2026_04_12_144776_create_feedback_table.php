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
     Schema::create('feedback', function (Blueprint $table) {
        $table->id();

        $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
        $table->foreignId('student_id')->constrained()->cascadeOnDelete();
        $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();

        $table->integer('rating')->nullable();
        $table->text('comment')->nullable();
        $table->timestamp('submitted_at')->nullable();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
