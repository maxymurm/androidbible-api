<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unified markers table: bookmarks (kind=0), notes (kind=1), highlights (kind=2)
        Schema::create('markers', function (Blueprint $table) {
            $table->id();
            $table->uuid('gid')->unique(); // Global ID for sync
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('kind'); // 0=bookmark, 1=note, 2=highlight
            $table->integer('ari')->unsigned(); // Absolute Reference Integer
            $table->integer('ari_end')->unsigned()->nullable(); // End ARI for range highlights
            $table->string('bible_version_slug')->nullable(); // Which Bible version
            $table->text('caption')->nullable(); // Bookmark title or note content
            $table->integer('highlight_color')->nullable(); // Highlight color (ARGB int)
            $table->integer('verse_count')->default(1); // Number of verses covered
            $table->timestamp('marker_date')->useCurrent(); // When the marker was created
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'kind']);
            $table->index(['user_id', 'ari']);
            $table->index(['user_id', 'kind', 'ari']);
            $table->index('gid');
        });

        // Labels (categories/tags)
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->uuid('gid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('background_color', 10)->default('#2196F3'); // Hex color
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'sort_order']);
        });

        // Many-to-many: markers ↔ labels
        Schema::create('marker_label', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['marker_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marker_label');
        Schema::dropIfExists('labels');
        Schema::dropIfExists('markers');
    }
};
