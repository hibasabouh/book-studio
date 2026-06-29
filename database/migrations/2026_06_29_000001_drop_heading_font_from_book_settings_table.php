<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_settings', function (Blueprint $table) {
            $table->dropColumn('heading_font');
        });
    }

    public function down(): void
    {
        Schema::table('book_settings', function (Blueprint $table) {
            $table->string('heading_font')->default('playfair_display')->after('arabic_font');
        });
    }
};
