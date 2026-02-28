<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sync events table: event sourcing for cross-device sync
        Schema::create('sync_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique(); // Unique event identifier
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type'); // 'marker', 'label', 'progress_mark', etc.
            $table->string('entity_gid'); // GID of the entity
            $table->string('action'); // 'create', 'update', 'delete'
            $table->json('payload'); // Full entity data at time of event
            $table->json('changed_fields')->nullable(); // Which fields changed (for updates)
            $table->string('device_id')->nullable(); // Which device originated this
            $table->bigInteger('version'); // Monotonically increasing per-user version
            $table->timestamp('event_timestamp')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'version']);
            $table->index(['user_id', 'entity_type', 'entity_gid']);
            $table->index(['user_id', 'event_timestamp']);
        });

        // Sync state: tracks each device's sync position
        Schema::create('sync_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id');
            $table->bigInteger('last_synced_version')->default(0); // Last version this device has seen
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
        });

        // User preferences (synced across devices)
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('active_bible_version_slug')->nullable();
            $table->integer('active_book_id')->nullable();
            $table->integer('active_chapter')->nullable();
            $table->integer('active_verse')->nullable();
            $table->float('font_size')->default(16.0);
            $table->string('font_family')->default('system');
            $table->float('line_spacing')->default(1.5);
            $table->boolean('night_mode')->default(false);
            $table->string('theme')->default('system'); // system, light, dark
            $table->boolean('continuous_scroll')->default(true);
            $table->boolean('show_verse_numbers')->default(true);
            $table->boolean('show_red_letters')->default(true);
            $table->json('extra')->nullable(); // Additional preferences as JSON
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('sync_states');
        Schema::dropIfExists('sync_events');
    }
};
