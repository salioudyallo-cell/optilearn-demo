<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score_pct');
            $table->boolean('passed')->default(false)->index();
            // Sur MariaDB, `json` n'est qu'un alias de LONGTEXT : ce contenu ne doit JAMAIS
            // etre interroge en SQL. Lecture et ecriture par le cast Eloquent uniquement.
            $table->json('answers');
            $table->timestamp('attempted_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
