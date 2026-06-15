<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_requests', 'class_id')) {
                $table->foreignId('class_id')->nullable()->after('teacher_id')
                      ->constrained('classes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_requests', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_requests', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            }
        });
    }
};
