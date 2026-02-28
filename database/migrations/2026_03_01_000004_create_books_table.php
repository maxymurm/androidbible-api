<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_version_id')->constrained()->cascadeOnDelete();
            $table->integer('book_id'); // 1-66 (standard), 67+ for apocrypha
            $table->string('short_name', 20); // e.g. 'Gen', 'Mat'
            $table->string('name'); // e.g. 'Genesis', 'Matthew'
            $table->integer('chapter_count');
            $table->integer('verse_count')->default(0);
            $table->enum('testament', ['OT', 'NT', 'AP']); // Old, New, Apocrypha
            $table->integer('sort_order');
            $table->timestamps();

            $table->unique(['bible_version_id', 'book_id']);
            $table->index('book_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
