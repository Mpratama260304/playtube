<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->string('role')->default('user')->after('password'); // admin, user
            $table->string('avatar_path')->nullable()->after('role');
            $table->string('cover_path')->nullable()->after('avatar_path');
            $table->text('bio')->nullable()->after('cover_path');
            $table->boolean('is_banned')->default(false)->after('bio');
            $table->boolean('is_active')->default(true)->after('is_banned');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'avatar_path', 'cover_path', 'bio', 'is_banned', 'is_active']);
        });
    }
};
