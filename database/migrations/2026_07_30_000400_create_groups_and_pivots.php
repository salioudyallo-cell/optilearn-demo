<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Groupes d'apprenants d'une organisation, appartenance des apprenants aux groupes, et
 * affectation des formations à un groupe. L'affectation matérialise des inscriptions
 * (le cœur d'accès reste inchangé : hasAccessToCourse s'appuie sur les inscriptions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('group_user', function (Blueprint $table): void {
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['group_id', 'user_id']);
        });

        Schema::create('course_group', function (Blueprint $table): void {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->primary(['course_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_group');
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
    }
};
