<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_sale', function (Blueprint $table) {
            $table->foreignId('bonus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->primary(['bonus_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_sale');
    }
};