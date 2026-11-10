<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');

            // 'offered' = user knows this skill | 'wanted' = user wants to learn this skill
            $table->enum('type', ['offered', 'wanted']);

            // Optional: helps filter by experience (e.g., Beginner, Intermediate, Expert)
            $table->enum('proficiency', ['beginner', 'intermediate', 'expert'])->nullable();
            $table->timestamps();

            // Prevent a user from adding the exact same skill twice under the same type
            $table->unique(['user_id', 'skill_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_skills');
    }
};
