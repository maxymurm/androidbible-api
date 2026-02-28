<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration_days');
            $table->string('language', 10)->default('en');
            $table->string('category')->nullable(); // e.g. 'chronological', 'thematic', 'devotional'
            $table->string('thumbnail')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('language');
        });

        Schema::create('reading_plan_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_plan_id')->constrained()->cascadeOnDelete();
            $table->integer('day_number');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('ari_ranges'); // Array of {from_ari, to_ari} for the day's readings
            $table->timestamps();

            $table->unique(['reading_plan_id', 'day_number']);
        });

        Schema::create('reading_plan_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_plan_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->integer('current_day')->default(1);
            $table->json('completed_days')->default('[]'); // Array of completed day numbers
            $table->enum('status', ['active', 'completed', 'paused', 'abandoned'])->default('active');
            $table->string('bible_version_slug')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'reading_plan_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_plan_progress');
        Schema::dropIfExists('reading_plan_days');
        Schema::dropIfExists('reading_plans');
    }
};
