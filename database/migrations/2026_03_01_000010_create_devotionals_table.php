<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devotionals', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('body');
            $table->date('publish_date');
            $table->string('author')->nullable();
            $table->json('ari_references')->nullable(); // Array of ARIs for referenced verses
            $table->string('language', 10)->default('en');
            $table->string('thumbnail')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index('publish_date');
            $table->index('language');
            $table->index('is_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devotionals');
    }
};
