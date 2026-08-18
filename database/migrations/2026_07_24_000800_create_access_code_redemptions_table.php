<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'utilisation des codes : on doit pouvoir repondre a
 * « qui a utilise ce code, et quand ». L'unicite (code, utilisateur) est aussi
 * le garde-fou de dernier recours contre une double activation concurrente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->unique(['access_code_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_code_redemptions');
    }
};
