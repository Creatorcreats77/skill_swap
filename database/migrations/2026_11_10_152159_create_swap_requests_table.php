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
        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();

            // Who initiated the request and who received it
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');

            // What skills are being traded
            $table->foreignId('offered_skill_id')->constrained('skills')->onDelete('cascade'); // Sender teaches this
            $table->foreignId('requested_skill_id')->constrained('skills')->onDelete('cascade'); // Sender wants to learn this

            $table->enum('status', ['pending', 'accepted', 'declined', 'completed'])->default('pending');
            $table->text('initial_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swap_requests');
    }
};
