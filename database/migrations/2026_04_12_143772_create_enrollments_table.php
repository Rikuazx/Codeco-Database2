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
        Schema::create('enrollments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('student_id')->constrained()->cascadeOnDelete();
        $table->foreignId('class_id')->constrained()->cascadeOnDelete();

        $table->decimal('price', 12, 2);

        $table->enum('status', ['pending', 'active', 'completed']);

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
