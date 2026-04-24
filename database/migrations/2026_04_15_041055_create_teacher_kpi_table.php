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

    Schema::create('teacher_kpis', function (Blueprint $table) {
    $table->id();
    $table->foreignId('teacher_id')->constrained()->onDelete('cascade');

    $table->integer('month'); // 1–12
    $table->integer('year');

    //  scores
    $table->enum('category', ['A', 'B', 'C'])->nullable();
    $table->text('notes')->nullable(); // optional admin notes

    $table->decimal('feedback_score', 5, 2)->default(0);
    $table->decimal('attendance_score', 5, 2)->default(0);
    $table->decimal('availability_score', 5, 2)->default(0);

    $table->decimal('total_score', 5, 2)->default(0);

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_kpi');
    }
};
