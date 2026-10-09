<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('path')->nullable();
            $table->string('failure')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_exports');
    }
};
