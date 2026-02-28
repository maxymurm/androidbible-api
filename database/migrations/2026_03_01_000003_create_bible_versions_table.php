<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_versions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g. 'kjv', 'esv', 'niv'
            $table->string('short_name', 20); // e.g. 'KJV'
            $table->string('name'); // e.g. 'King James Version'
            $table->string('language', 10); // ISO 639-1 code
            $table->string('language_name'); // e.g. 'English'
            $table->text('description')->nullable();
            $table->string('publisher')->nullable();
            $table->string('copyright')->nullable();
            $table->integer('year')->nullable();
            $table->boolean('has_old_testament')->default(true);
            $table->boolean('has_new_testament')->default(true);
            $table->boolean('has_apocrypha')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('verse_count')->default(0);
            $table->string('text_direction', 4)->default('ltr'); // ltr, rtl
            $table->timestamps();

            $table->index('language');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_versions');
    }
};
