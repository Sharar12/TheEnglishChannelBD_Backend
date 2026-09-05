<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bkash_number', 20)->nullable();
            $table->string('nagad_number', 20)->nullable();
            $table->decimal('cod_charge', 10, 2)->default(0);
            $table->decimal('bkash_discount_percent', 5, 2)->default(0);
            $table->decimal('nagad_discount_percent', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
