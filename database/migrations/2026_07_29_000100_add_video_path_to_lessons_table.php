<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chemin d'une video auto-hebergee, relatif au disque « videos ».
 * Coexiste avec bunny_video_id : une lecon peut etre servie par l'un ou l'autre
 * pilote sans etre recreee lors de la bascule vers Bunny Stream.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->string('video_path')->nullable()->after('bunny_video_id');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropColumn('video_path');
        });
    }
};
