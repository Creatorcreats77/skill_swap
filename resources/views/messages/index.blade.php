@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    {{ __('Messages') }}
</h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="glass rounded-2xl p-3 sm:p-4 animate-fade-up">
            @forelse ($conversations as $conversation)
                @php
                    $other = $conversation->otherParticipant(auth()->user());
                    $last = $conversation->latestMessage;
                    $unread = $conversation->unread_count;
                @endphp

                <a href="{{ route('messages.show', $other) }}"
                    class="flex items-center gap-4 rounded-xl p-3 hover:bg-white/50 transition {{ $unread ? 'bg-white/40' : '' }}">
                    <x-avatar :user="$other" size="md" />

                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="font-semibold text-gray-800 truncate">{{ $other->name }}</span>
                            <span class="text-xs text-gray-500 shrink-0">{{ $conversation->last_message_at?->diffForHumans(short: true) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm truncate {{ $unread ? 'text-gray-900 font-semibold' : 'text-gray-600' }}">
                                @if ($last && (int) $last->sender_id === auth()->id())<span class="text-gray-500 font-normal">You:</span>@endif
                                {{ $last?->body }}
                            </p>
                            @if ($unread)
                                <span class="shrink-0 rounded-full bg-emerald-600 px-2 text-xs font-bold text-white">{{ $unread }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-12 text-gray-600">
                    <p class="text-base">No messages yet.</p>
                    <p class="text-sm mt-1">
                        Find someone on <a href="{{ route('matches.index') }}" class="text-emerald-700 font-semibold hover:underline">Find Matches</a>
                        and press <span class="font-semibold">💬 Message</span>.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
