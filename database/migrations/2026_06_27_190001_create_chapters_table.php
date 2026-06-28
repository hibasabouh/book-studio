<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['book_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
