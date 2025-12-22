<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('visibility')->default('public'); // public, private
            $table->timestamps();

            $table->index(['user_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
