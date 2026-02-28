<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pericopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_version_id')->constrained()->cascadeOnDelete();
            $table->integer('ari')->unsigned(); // ARI of the first verse of the pericope
            $table->string('title'); // Section heading text
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bible_version_id', 'ari']);
        });

        Schema::create('cross_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_version_id')->constrained()->cascadeOnDelete();
            $table->integer('from_ari')->unsigned(); // Source verse ARI
            $table->integer('to_ari')->unsigned(); // Target verse ARI
            $table->integer('to_ari_end')->unsigned()->nullable(); // End of range if range reference
            $table->timestamps();

            $table->index(['bible_version_id', 'from_ari']);
            $table->index(['bible_version_id', 'to_ari']);
        });

        Schema::create('footnotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_version_id')->constrained()->cascadeOnDelete();
            $table->integer('ari')->unsigned();
            $table->text('content');
            $table->string('field')->nullable(); // Which part of the verse this note refers to
            $table->timestamps();

            $table->index(['bible_version_id', 'ari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footnotes');
        Schema::dropIfExists('cross_references');
        Schema::dropIfExists('pericopes');
    }
};
