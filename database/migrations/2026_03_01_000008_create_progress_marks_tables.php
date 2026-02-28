<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Progress marks (pins) - 5 preset positions like the legacy app
        Schema::create('progress_marks', function (Blueprint $table) {
            $table->id();
            $table->uuid('gid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('preset_id'); // 0-4 (5 pin positions)
            $table->integer('ari')->unsigned(); // Current position ARI
            $table->string('bible_version_slug')->nullable();
            $table->string('caption')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'preset_id']);
        });

        // Progress mark history (tracking reading progress over time)
        Schema::create('progress_mark_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('progress_mark_id')->constrained()->cascadeOnDelete();
            $table->integer('ari')->unsigned();
            $table->string('bible_version_slug')->nullable();
            $table->timestamp('progress_date')->useCurrent();
            $table->timestamps();

            $table->index(['progress_mark_id', 'progress_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_mark_history');
        Schema::dropIfExists('progress_marks');
    }
};
