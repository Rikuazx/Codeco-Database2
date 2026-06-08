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
        Schema::create('schedule_change_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('class_session_id')->constrained()->onDelete('cascade');
        $table->foreignId('teacher_id')->constrained()->onDelete('cascade');

        $table->text('reason')->nullable();
        $table->string('proof_file')->nullable();

        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

        $table->timestamp('new_start_time')->nullable();
        $table->timestamp('new_end_time')->nullable();
        $table->timestamp('requested_at');
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_change_requests');
    }
};
