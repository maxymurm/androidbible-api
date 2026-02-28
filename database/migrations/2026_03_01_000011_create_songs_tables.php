<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('song_books', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('publisher')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_book_id')->constrained()->cascadeOnDelete();
            $table->integer('number'); // Song number in the book
            $table->string('title');
            $table->string('title_original')->nullable();
            $table->string('author')->nullable();
            $table->string('tune')->nullable();
            $table->string('key_signature', 10)->nullable();
            $table->text('lyrics'); // Full lyrics text
            $table->text('lyrics_formatted')->nullable(); // HTML formatted
            $table->json('ari_references')->nullable(); // Referenced Bible verses
            $table->string('audio_url')->nullable();
            $table->timestamps();

            $table->unique(['song_book_id', 'number']);
            $table->index('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
        Schema::dropIfExists('song_books');
    }
};
