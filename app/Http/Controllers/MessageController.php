<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Inbox: all of the user's conversations, most recent first.
     */
    public function index(Request $request): View
    {
        $me = $request->user();

        $conversations = Conversation::forUser($me)
            ->whereNotNull('last_message_at')
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $q) => $q
                ->where('is_read', false)
                ->where('sender_id', '!=', $me->id)])
            ->orderByDesc('last_message_at')
            ->get();

        return view('messages.index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Chat with one person, plus the swap requests between you and their contact card.
     */
    public function show(Request $request, User $user): View|RedirectResponse
    {
        $me = $request->user();

        if ($me->is($user)) {
            return redirect()->route('messages.index');
        }

        $conversation = Conversation::findBetween($me, $user);

        $messages = collect();
        if ($conversation) {
            $conversation->messages()
                ->where('sender_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $messages = $conversation->messages()->with('sender')->oldest()->orderBy('id')->get();
        }

        $swapRequests = SwapRequest::query()
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q->where('sender_id', $me->id)->where('receiver_id', $user->id))
                ->orWhere(fn (Builder $q) => $q->where('sender_id', $user->id)->where('receiver_id', $me->id)))
            ->with(['offeredSkill', 'requestedSkill'])
            ->latest()
            ->get();

        return view('messages.show', [
            'other' => $user,
            'messages' => $messages,
            'swapRequests' => $swapRequests,
            'canSeeContact' => $me->canSeeContactOf($user),
        ]);
    }

    public function store(Request $request, User $user): RedirectResponse
    {
        $me = $request->user();

        abort_if($me->is($user), 422, 'You cannot message yourself.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Conversation::between($me, $user)->send($me, trim($validated['body']));

        return redirect()->to(route('messages.show', $user).'#latest');
    }
}
