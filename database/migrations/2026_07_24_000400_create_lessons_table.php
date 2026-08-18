<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('title');
            $table->enum('type', ['video', 'text', 'pdf', 'quiz'])->index();
            // Jamais rendu dans le HTML : sert uniquement a signer une URL cote serveur.
            $table->string('bunny_video_id')->nullable();
            $table->longText('content')->nullable();
            $table->string('asset_path')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_preview')->default(false)->index();
            $table->timestamps();

            $table->unique(['module_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
