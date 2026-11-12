<?php

namespace App\Http\Controllers;

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

        $user->sentSwapRequests()->create([
            'receiver_id' => $validated['receiver_id'],
            'offered_skill_id' => $validated['offered_skill_id'],
            'requested_skill_id' => $validated['requested_skill_id'],
            'initial_message' => $validated['initial_message'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Swap request sent!');
    }

    /**
     * Conversation view for a single request.
     */
    public function show(Request $request, SwapRequest $swapRequest): View
    {
        $this->ensureParticipant($request->user(), $swapRequest);

        $swapRequest->load([
            'sender',
            'receiver',
            'offeredSkill',
            'requestedSkill',
            'messages' => fn ($query) => $query->with('sender')->oldest()->orderBy('id'),
        ]);

        // Mark the other person's messages as read.
        $swapRequest->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('swap-requests.show', [
            'swapRequest' => $swapRequest,
        ]);
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

    public function storeMessage(Request $request, SwapRequest $swapRequest): RedirectResponse
    {
        $user = $request->user();
        $this->ensureParticipant($user, $swapRequest);

        abort_if($swapRequest->status === 'declined', 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $swapRequest->messages()->create([
            'sender_id' => $user->id,
            'body' => $validated['body'],
        ]);

        return redirect()->route('swap.request.show', $swapRequest);
    }

    private function ensureParticipant(User $user, SwapRequest $swapRequest): void
    {
        abort_unless($swapRequest->involves($user), 403);
    }
}
