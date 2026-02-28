<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which versions a user has downloaded/enabled
        Schema::create('user_bible_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bible_version_id')->constrained()->onDelete('cascade');
            $table->boolean('is_downloaded')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'bible_version_id']);
            $table->index(['user_id', 'is_downloaded']);
        });

        // Search history for suggestions
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('query', 200);
            $table->string('version_slug')->nullable();
            $table->integer('results_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Reading history for recently read chapters
        Schema::create('reading_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bible_version_id')->constrained()->onDelete('cascade');
            $table->integer('book_id');
            $table->integer('chapter_num');
            $table->integer('ari');
            $table->integer('scroll_position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
            $table->unique(['user_id', 'bible_version_id', 'book_id', 'chapter_num']);
        });

        // Add download_url and file_size to bible_versions for version manager
        Schema::table('bible_versions', function (Blueprint $table) {
            $table->string('download_url')->nullable()->after('text_direction');
            $table->bigInteger('file_size')->default(0)->after('download_url');
            $table->integer('download_count')->default(0)->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_history');
        Schema::dropIfExists('search_history');
        Schema::dropIfExists('user_bible_versions');

        Schema::table('bible_versions', function (Blueprint $table) {
            $table->dropColumn(['download_url', 'file_size', 'download_count']);
        });
    }
};
