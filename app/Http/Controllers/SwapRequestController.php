<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SwapRequestController extends Controller
{
    /**
     * Inbox: requests the user has received and sent.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $relations = ['sender', 'receiver', 'offeredSkill', 'requestedSkill'];

        return view('swap-requests.index', [
            'received' => $user->receivedSwapRequests()->with($relations)->latest()->get(),
            'sent' => $user->sentSwapRequests()->with($relations)->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', Rule::exists('users', 'id'), Rule::notIn([$user->id])],
            'offered_skill_id' => ['required', 'integer', 'exists:skills,id'],
            'requested_skill_id' => ['required', 'integer', 'exists:skills,id', 'different:offered_skill_id'],
            'initial_message' => ['nullable', 'string', 'max:1000'],
        ]);

        // The receiver must actually teach what the sender wants and want what the sender teaches.
        $isMatch = User::whereKey($validated['receiver_id'])
            ->matching((int) $validated['requested_skill_id'], (int) $validated['offered_skill_id'])
            ->exists();

        if (! $isMatch) {
            throw ValidationException::withMessages([
                'receiver_id' => 'This user is no longer a match for that skill swap.',
            ]);
        }

        $alreadyPending = $user->sentSwapRequests()
            ->where('receiver_id', $validated['receiver_id'])
            ->where('offered_skill_id', $validated['offered_skill_id'])
            ->where('requested_skill_id', $validated['requested_skill_id'])
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->with('success', 'You already have a pending request with this user for that swap.');
        }

        $swapRequest = $user->sentSwapRequests()->create([
            'receiver_id' => $validated['receiver_id'],
            'offered_skill_id' => $validated['offered_skill_id'],
            'requested_skill_id' => $validated['requested_skill_id'],
            'initial_message' => $validated['initial_message'] ?? null,
            'status' => 'pending',
        ]);

        // The note also lands in the private chat, so the receiver sees it in Messages.
        if (filled($swapRequest->initial_message)) {
            Conversation::between($user, User::findOrFail($validated['receiver_id']))
                ->send($user, $swapRequest->initial_message, $swapRequest);
        }

        return back()->with('success', 'Swap request sent!');
    }

    /**
     * Requests are discussed in the private chat with the other person.
     */
    public function show(Request $request, SwapRequest $swapRequest): RedirectResponse
    {
        $user = $request->user();
        $this->ensureParticipant($user, $swapRequest);

        return redirect()->route('messages.show', $swapRequest->otherParty($user));
    }

    /**
     * Accept / decline (receiver), cancel (sender) or mark completed (either, once accepted).
     */
    public function update(Request $request, SwapRequest $swapRequest): RedirectResponse
    {
        $user = $request->user();
        $this->ensureParticipant($user, $swapRequest);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'declined', 'completed'])],
        ]);

        $status = $validated['status'];
        $isReceiver = (int) $swapRequest->receiver_id === $user->id;

        $allowed = match ($status) {
            'accepted' => $isReceiver && $swapRequest->status === 'pending',
            // The receiver declines; the sender can withdraw their own pending request the same way.
            'declined' => $swapRequest->status === 'pending',
            'completed' => $swapRequest->status === 'accepted',
        };

        abort_unless($allowed, 403);

        $swapRequest->update(['status' => $status]);

        return back()->with('success', "Swap request marked as {$status}.");
    }

    private function ensureParticipant(User $user, SwapRequest $swapRequest): void
    {
        abort_unless($swapRequest->involves($user), 403);
    }
}
