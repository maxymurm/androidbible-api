<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->integer('ari')->unsigned(); // Absolute Reference Integer: (bookId << 16) | (chapter << 8) | verse
            $table->integer('chapter_num');
            $table->integer('verse_num');
            $table->text('text'); // Plain text content
            $table->text('text_formatted')->nullable(); // HTML/formatted content
            $table->timestamps();

            $table->unique(['bible_version_id', 'ari']);
            $table->index('ari');
            $table->index(['bible_version_id', 'book_id', 'chapter_num']);
            $table->index(['bible_version_id', 'book_id', 'chapter_num', 'verse_num'], 'verses_full_ref_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verses');
    }
};
