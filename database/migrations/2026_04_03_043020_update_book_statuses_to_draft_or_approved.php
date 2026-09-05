<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $booksToUpdate = DB::table('books')
            ->whereIn('status', ['pending', 'rejected'])
            ->select('id')
            ->get();

        foreach ($booksToUpdate as $book) {
            DB::table('books')
                ->where('id', $book->id)
                ->update(['status' => rand(0, 1) < 0.5 ? 'draft' : 'approved']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            //
        });
    }
};
