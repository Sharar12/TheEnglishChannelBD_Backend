<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_quizzes', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable()->constrained('course_lessons')->cascadeOnDelete()->after('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_quizzes', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropColumn('lesson_id');
        });
    }
};
