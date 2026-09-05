<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_course_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('access_type'); // 'free' or 'discount'
            $table->unsignedInteger('discount_percent')->nullable();
            $table->timestamps();

            $table->unique(['book_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_course_access');
    }
};
