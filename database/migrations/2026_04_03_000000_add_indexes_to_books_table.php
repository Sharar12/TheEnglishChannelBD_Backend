<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->index('status');
            $table->index('stock');
            $table->index('is_featured');
            $table->index('category_id');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
        });
        
        DB::statement('ALTER TABLE books ADD FULLTEXT INDEX books_search_index (title, author)');
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['stock']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status', 'created_at']);
        });
        
        DB::statement('ALTER TABLE books DROP INDEX books_search_index');
    }
};
