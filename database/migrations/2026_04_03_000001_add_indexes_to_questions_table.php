<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->index('book_id');
            $table->index('user_id');
            $table->index('is_answered');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['book_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_answered']);
            $table->dropIndex(['created_at']);
        });
    }
};
