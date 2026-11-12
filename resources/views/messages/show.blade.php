@extends('layouts.app')

@section('header')
<div class="flex items-center gap-4">
    <a href="{{ route('messages.index') }}" class="text-gray-600 hover:text-emerald-700 text-xl" title="All messages">←</a>
    <a href="{{ route('users.show', $other) }}" class="group flex items-center gap-3 min-w-0">
        <x-avatar :user="$other" size="md" />
        <div class="min-w-0">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight group-hover:underline truncate">{{ $other->name }}</h2>
            <p class="text-xs text-gray-600 truncate">{{ $other->bio ?: 'View profile' }}</p>
        </div>
    </a>
</div>
@endsection

@section('content')
<div class="py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Chat --}}
        <div class="lg:col-span-2 space-y-4">
            @include('components.success')
            @include('components.error')

            <div class="glass rounded-2xl p-4 sm:p-6 animate-fade-up">
                <div id="messages" class="space-y-3 h-[28rem] max-h-[60vh] overflow-y-auto pr-1">
                    @forelse ($messages as $message)
                        @php $mine = (int) $message->sender_id === auth()->id(); @endphp
                        <div class="flex items-end gap-2 {{ $mine ? 'justify-end' : 'justify-start' }}">
                            @unless ($mine)<x-avatar :user="$message->sender" size="sm" />@endunless
                            <div class="max-w-[80%] rounded-2xl px-4 py-2 {{ $mine ? 'bg-emerald-600 text-white rounded-br-sm' : 'bg-white/80 text-gray-800 rounded-bl-sm' }}">
                                <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                                <p class="text-xs mt-1 {{ $mine ? 'text-emerald-100' : 'text-gray-500' }}">
                                    {{ $message->created_at->format('M j, H:i') }}
                                    @if ($message->swap_request_id) · about a swap request @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center text-center text-gray-600">
                            <x-avatar :user="$other" size="lg" class="mb-3" />
                            <p class="text-base">Start a conversation with {{ $other->name }}.</p>
                            <p class="text-sm mt-1">Say hi and suggest what you could teach each other 👋</p>
                        </div>
                    @endforelse
                    <div id="latest"></div>
                </div>

                <form method="POST" action="{{ route('messages.store', $other) }}" class="mt-4 flex gap-2" id="message-form">
                    @csrf
                    <label for="body" class="sr-only">Message</label>
                    <textarea id="body" name="body" rows="2" required maxlength="2000" autofocus
                        placeholder="Write a message… (Enter to send, Shift+Enter for a new line)"
                        class="flex-1 glass-input">{{ old('body') }}</textarea>
                    <button type="submit"
                        class="self-end min-h-12 bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-semibold py-3 px-5 rounded-xl shadow">
                        Send
                    </button>
                </form>
            </div>
        </div>

        {{-- Sidebar: swaps between you + contacts --}}
        <aside class="space-y-4">
            <div class="glass rounded-2xl p-4">
                <h3 class="font-semibold text-gray-900 mb-3">🤝 Your swaps with {{ $other->name }}</h3>

                @forelse ($swapRequests as $swapRequest)
                    @php
                        $iSent = (int) $swapRequest->sender_id === auth()->id();
                        $youLearn = $iSent ? $swapRequest->requestedSkill : $swapRequest->offeredSkill;
                        $youTeach = $iSent ? $swapRequest->offeredSkill : $swapRequest->requestedSkill;
                    @endphp
                    <div class="glass-soft rounded-xl p-3 mb-3 last:mb-0 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-gray-500">{{ $iSent ? 'You asked' : 'They asked' }} · {{ $swapRequest->created_at->diffForHumans() }}</span>
                            @include('swap-requests.partials.status-badge', ['status' => $swapRequest->status])
                        </div>
                        <p class="text-sm text-gray-700">
                            You learn <span class="font-semibold text-green-700">{{ $youLearn->name }}</span>,
                            you teach <span class="font-semibold text-blue-700">{{ $youTeach->name }}</span>
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @include('swap-requests.partials.actions', ['swapRequest' => $swapRequest])
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">
                        No swap requests yet. You can send one from
                        <a href="{{ route('matches.index') }}" class="text-emerald-700 font-semibold hover:underline">Find Matches</a>.
                    </p>
                @endforelse
            </div>

            @include('profile.partials.contact-card', ['user' => $other, 'canSeeContact' => $canSeeContact])
        </aside>
    </div>
</div>

<script>
    // Start scrolled to the newest message; Enter sends, Shift+Enter adds a new line.
    const messages = document.getElementById('messages');
    if (messages) messages.scrollTop = messages.scrollHeight;

    const body = document.getElementById('body');
    body.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            if (body.value.trim() !== '') document.getElementById('message-form').requestSubmit();
        }
    });
</script>
@endsection
