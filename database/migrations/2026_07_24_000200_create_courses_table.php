<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_path')->nullable();
            // Prix de vitrine uniquement : aucun paiement en ligne dans ce perimetre.
            $table->unsignedInteger('price_fcfa')->default(0);
            $table->enum('level', ['debutant', 'intermediaire', 'avance'])->default('debutant')->index();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
