@extends('layouts.app')

@php
    $other = $swapRequest->otherParty(auth()->user());
@endphp

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            Swap with {{ $other->name }}
            @include('swap-requests.partials.status-badge', ['status' => $swapRequest->status])
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            {{ $swapRequest->sender->name }} teaches <span class="font-semibold">{{ $swapRequest->offeredSkill->name }}</span>
            · {{ $swapRequest->receiver->name }} teaches <span class="font-semibold">{{ $swapRequest->requestedSkill->name }}</span>
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @include('swap-requests.partials.actions', ['swapRequest' => $swapRequest])
    </div>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @include('components.success')
        @include('components.error')

        <div class="glass rounded-2xl p-6 animate-fade-up">
            <div class="space-y-3 max-h-[28rem] overflow-y-auto pr-1" id="messages">
                @if ($swapRequest->initial_message)
                    <div class="flex {{ (int) $swapRequest->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-xl px-4 py-2 text-sm bg-white/70 text-gray-800">
                            <p class="text-xs font-semibold text-gray-500 mb-0.5">{{ $swapRequest->sender->name }} · request note</p>
                            <p class="whitespace-pre-line">{{ $swapRequest->initial_message }}</p>
                        </div>
                    </div>
                @endif

                @forelse ($swapRequest->messages as $message)
                    @php $mine = (int) $message->sender_id === auth()->id(); @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-xl px-4 py-2 text-sm {{ $mine ? 'bg-emerald-600 text-white' : 'bg-white/70 text-gray-800' }}">
                            <p class="text-xs font-semibold mb-0.5 {{ $mine ? 'text-emerald-100' : 'text-gray-500' }}">
                                {{ $message->sender->name }} · {{ $message->created_at->diffForHumans() }}
                            </p>
                            <p class="whitespace-pre-line">{{ $message->body }}</p>
                        </div>
                    </div>
                @empty
                    @unless ($swapRequest->initial_message)
                        <p class="text-sm text-gray-500 text-center py-6">No messages yet. Say hello!</p>
                    @endunless
                @endforelse
            </div>

            @if ($swapRequest->status !== 'declined')
                <form method="POST" action="{{ route('swap.request.messages.store', $swapRequest) }}" class="mt-4 flex gap-2">
                    @csrf
                    <label for="body" class="sr-only">Message</label>
                    <textarea id="body" name="body" rows="2" required maxlength="2000" placeholder="Write a message…"
                        class="flex-1 glass-input">{{ old('body') }}</textarea>
                    <button type="submit"
                        class="self-end min-h-12 bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-semibold py-3 px-5 rounded-xl shadow">
                        Send
                    </button>
                </form>
            @else
                <p class="mt-4 text-sm text-gray-500 text-center">This request was declined, so the chat is closed.</p>
            @endif
        </div>

        <a href="{{ route('swap.request.index') }}" class="inline-block text-sm text-gray-700 hover:underline">← All requests</a>
    </div>
</div>

<script>
    // Start the conversation scrolled to the newest message.
    const messages = document.getElementById('messages');
    if (messages) messages.scrollTop = messages.scrollHeight;
</script>
@endsection
