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
        Schema::table('course_progress', function (Blueprint $table) {
            $table->boolean('watched')->default(false)->after('completed');
            $table->integer('video_seconds')->nullable()->after('watched');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_progress', function (Blueprint $table) {
            $table->dropColumn(['watched', 'video_seconds']);
        });
    }
};
