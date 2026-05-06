<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')->unique()->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('classes')->cascadeOnDelete();

            $table->string('certificate_number')->unique()->nullable();      // nomor sertifikat unik
            $table->string('certificate_url')->nullable();                   // link/path file sertifikat PDF

            $table->timestamp('issued_at')->nullable();
            $table->enum('issued_by', ['admin', 'system'])->default('system');
            $table->enum('certification_status', ['pending', 'issued', 'revoked'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
