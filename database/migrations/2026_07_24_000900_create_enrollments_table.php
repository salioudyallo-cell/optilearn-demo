<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active')->index();
            $table->enum('source', ['access_code', 'manual', 'purchase'])->index();
            $table->foreignId('access_code_id')->nullable()->constrained()->nullOnDelete();
            // Reste nullable et inutilise tant que le paiement en ligne est hors perimetre.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('enrolled_at');
            // null = acces a vie.
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
