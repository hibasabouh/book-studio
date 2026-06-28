<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('template')->default('classic');
            $table->string('latin_font')->default('eb_garamond');
            $table->string('arabic_font')->default('amiri');
            $table->string('heading_font')->default('playfair_display');
            $table->string('text_direction')->default('ltr');
            $table->decimal('font_size', 4, 1)->default(12.0);
            $table->decimal('chapter_header_font_size', 4, 1)->default(18.0);
            $table->decimal('line_height', 3, 1)->default(1.5);
            $table->string('author')->nullable();
            $table->string('subtitle')->nullable();
            $table->boolean('show_page_numbers')->default(true);
            $table->boolean('show_table_of_contents')->default(true);
            $table->boolean('show_chapter_headers')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_settings');
    }
};
