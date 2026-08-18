<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'événements produit, alimentant les KPI internes (apprenants actifs,
 * activation, complétion…). Stockage en base, sans dépendance externe : cohérent avec
 * l'hébergement mutualisé et respectueux de la vie privée (aucune donnée envoyée à un
 * tiers). Un outil web (Matomo/Plausible) reste complémentaire pour l'audience.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_events', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_events');
    }
};
