<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Direct messages: one conversation per pair of users. Messages now belong to a
 * conversation; swap_request_id becomes optional context. Existing swap-request
 * chats (and request notes) are moved into the matching conversation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // Always stored with user_one_id < user_two_id so each pair has exactly one row.
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['swap_request_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('swap_request_id')->nullable()->change();
            $table->foreign('swap_request_id')->references('id')->on('swap_requests')->nullOnDelete();
        });

        $this->moveSwapChatsIntoConversations();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Direct messages have no swap request and cannot survive the old schema.
        DB::table('messages')->whereNull('swap_request_id')->delete();

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['swap_request_id']);
            $table->dropConstrainedForeignId('conversation_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('swap_request_id')->nullable(false)->change();
            $table->foreign('swap_request_id')->references('id')->on('swap_requests')->cascadeOnDelete();
        });

        Schema::dropIfExists('conversations');
    }

    private function moveSwapChatsIntoConversations(): void
    {
        $swaps = DB::table('swap_requests')->orderBy('id')->get();

        foreach ($swaps as $swap) {
            $hasMessages = DB::table('messages')->where('swap_request_id', $swap->id)->exists();

            if (! $hasMessages && blank($swap->initial_message)) {
                continue;
            }

            $conversationId = $this->conversationIdFor((int) $swap->sender_id, (int) $swap->receiver_id);

            if (filled($swap->initial_message)) {
                // The note must come before the replies, even if they share a timestamp.
                $firstReplyAt = DB::table('messages')->where('swap_request_id', $swap->id)->min('created_at');
                $noteAt = $firstReplyAt && $firstReplyAt <= $swap->created_at
                    ? Carbon::parse($firstReplyAt)->subSecond()
                    : $swap->created_at;

                DB::table('messages')->insert([
                    'conversation_id' => $conversationId,
                    'swap_request_id' => $swap->id,
                    'sender_id' => $swap->sender_id,
                    'body' => $swap->initial_message,
                    'is_read' => true,
                    'created_at' => $noteAt,
                    'updated_at' => $noteAt,
                ]);
            }

            DB::table('messages')->where('swap_request_id', $swap->id)->update(['conversation_id' => $conversationId]);

            DB::table('conversations')->where('id', $conversationId)->update([
                'last_message_at' => DB::table('messages')->where('conversation_id', $conversationId)->max('created_at'),
            ]);
        }
    }

    private function conversationIdFor(int $a, int $b): int
    {
        [$one, $two] = [min($a, $b), max($a, $b)];

        $existing = DB::table('conversations')->where('user_one_id', $one)->where('user_two_id', $two)->value('id');

        return $existing ?? DB::table('conversations')->insertGetId([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
