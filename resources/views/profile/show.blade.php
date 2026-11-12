@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="glass rounded-2xl p-6 animate-fade-up">
            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <x-avatar :user="$user" size="xl" />
                <div class="min-w-0">
                    <h2 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
                    <p class="text-gray-700 mt-1 whitespace-pre-line">{{ $user->bio ?: 'Ready to swap skills!' }}</p>
                    <p class="text-xs text-gray-500 mt-2">Member since {{ $user->created_at?->format('F Y') }}</p>
                </div>
                @if (auth()->user()->is($user))
                    <a href="{{ route('profile.edit') }}"
                        class="sm:ml-auto inline-flex items-center min-h-12 bg-white/70 hover:bg-white text-emerald-700 font-semibold py-3 px-4 rounded-xl shadow">
                        ✏️ Edit profile
                    </a>
                @else
                    <a href="{{ route('messages.show', $user) }}"
                        class="sm:ml-auto inline-flex items-center justify-center min-h-12 bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-semibold py-3 px-5 rounded-xl shadow">
                        💬 Message
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="glass rounded-2xl p-6">
                <h3 class="font-semibold text-green-700 mb-3">🛠️ Teaches</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($user->offeredSkills as $skill)
                        <span class="bg-green-100 text-green-800 text-sm px-2.5 py-1 rounded">
                            {{ $skill->name }}@if ($skill->pivot->proficiency) <span class="text-xs opacity-70">· {{ ucfirst($skill->pivot->proficiency) }}</span>@endif
                        </span>
                    @empty
                        <p class="text-sm text-gray-500">Nothing listed yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="glass rounded-2xl p-6">
                <h3 class="font-semibold text-blue-700 mb-3">🎯 Wants to learn</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($user->wantedSkills as $skill)
                        <span class="bg-blue-100 text-blue-800 text-sm px-2.5 py-1 rounded">{{ $skill->name }}</span>
                    @empty
                        <p class="text-sm text-gray-500">Nothing listed yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @include('profile.partials.contact-card', ['user' => $user, 'canSeeContact' => $canSeeContact])
    </div>
</div>
@endsection
