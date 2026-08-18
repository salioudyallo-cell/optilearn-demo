<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email_verified_at');
            $table->string('company')->nullable()->after('phone');
            $table->string('country', 2)->nullable()->after('company');
            $table->enum('role', ['learner', 'instructor', 'admin'])
                ->default('learner')
                ->after('country')
                ->index();
            $table->string('avatar_path')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['phone', 'company', 'country', 'role', 'avatar_path']);
        });
    }
};
