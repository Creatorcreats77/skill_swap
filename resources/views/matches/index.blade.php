@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Find Skill Swap Partners') }}
    </h2>
    <p class="text-sm text-gray-600 mt-1">Everyone on SkillSwap, with what they teach and what they want to learn. Your best matches come first.</p>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @include('components.success')
            @include('components.error')

            @unless ($hasSkills)
                <div class="glass-soft rounded-2xl p-4 text-sm text-gray-700 animate-fade-up">
                    👋 You haven't added any skills yet.
                    <a href="{{ route('profile.skills.edit') }}" class="font-semibold text-emerald-700 hover:underline">Pick what you can teach and want to learn</a>
                    so we can highlight your best matches and other people can find you too.
                </div>
            @endunless

            <!-- Filters -->
            <div class="glass overflow-hidden rounded-2xl p-6 animate-fade-up">
                <form method="GET" action="{{ route('matches.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Search people…" class="w-full glass-input">
                    </div>

                    <div>
                        <label for="wanted_skill_id" class="block text-sm font-medium text-gray-700 mb-1">I want to learn</label>
                        <select id="wanted_skill_id" name="wanted_skill_id" class="w-full glass-input">
                            <option value="">Any skill</option>
                            @foreach ($allSkills as $skill)
                                <option value="{{ $skill->id }}" @selected($wantedSkillId === $skill->id)>{{ $skill->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="offered_skill_id" class="block text-sm font-medium text-gray-700 mb-1">I can teach in return</label>
                        <select id="offered_skill_id" name="offered_skill_id" class="w-full glass-input">
                            <option value="">Any skill</option>
                            @foreach ($allSkills as $skill)
                                <option value="{{ $skill->id }}" @selected($offeredSkillId === $skill->id)>{{ $skill->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 min-h-12 bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow">
                            Search 🔍
                        </button>
                        @if ($isFiltered)
                            <a href="{{ route('matches.index') }}"
                                class="min-h-12 inline-flex items-center bg-white/70 hover:bg-white text-gray-700 font-semibold py-3 px-4 rounded-xl shadow">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- People -->
            <div class="glass overflow-hidden rounded-2xl p-6 animate-fade-up [animation-delay:0.15s]">
                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ $isFiltered ? 'Matching people' : 'All people' }}</h3>
                    <p class="text-sm text-gray-600">{{ $people->total() }} {{ Str::plural('person', $people->total()) }}</p>
                </div>

                @if ($people->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 stagger">
                        @foreach ($people as $partner)
                            @php
                                $suggestion = $suggestions[$partner->id];
                                $canSendRequest = $suggestion['learn'] && $suggestion['teach'];
                                $isPending = $canSendRequest
                                    && in_array("{$partner->id}:{$suggestion['teach']}:{$suggestion['learn']}", $pendingPairs, true);
                            @endphp

                            <div class="glass-soft rounded-xl p-5 flex flex-col justify-between shadow-sm hover-lift {{ $suggestion['isMutual'] ? 'ring-2 ring-emerald-400/70' : '' }}">
                                <div>
                                    <a href="{{ route('users.show', $partner) }}" class="group flex items-center gap-3 mb-3">
                                        <x-avatar :user="$partner" size="lg" />
                                        <div class="min-w-0">
                                            <h4 class="font-bold text-gray-800 group-hover:text-emerald-700 group-hover:underline">{{ $partner->name }}</h4>
                                            <p class="text-xs text-gray-600 line-clamp-2">{{ $partner->bio ?: 'Ready to swap skills!' }}</p>
                                        </div>
                                    </a>

                                    @if ($suggestion['isMutual'])
                                        <p class="mb-3 inline-block text-xs font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">✨ Perfect match for you</p>
                                    @endif

                                    <div class="border-t border-white/50 pt-3 space-y-3 text-sm">
                                        <div>
                                            <span class="font-semibold text-green-700">🛠️ Teaches</span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @forelse ($partner->offeredSkills as $skill)
                                                    @php $highlight = in_array((int) $skill->id, $suggestion['canLearn'], true); @endphp
                                                    <span class="text-xs px-2 py-0.5 rounded {{ $highlight ? 'bg-green-600 text-white font-semibold' : 'bg-green-100 text-green-800' }}"
                                                        @if ($highlight) title="You want to learn this" @endif>
                                                        {{ $skill->name }}@if ($skill->pivot->proficiency) · {{ ucfirst($skill->pivot->proficiency) }}@endif
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-gray-500">Nothing yet</span>
                                                @endforelse
                                            </div>
                                        </div>

                                        <div>
                                            <span class="font-semibold text-blue-700">🎯 Wants to learn</span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @forelse ($partner->wantedSkills as $skill)
                                                    @php $highlight = in_array((int) $skill->id, $suggestion['canTeach'], true); @endphp
                                                    <span class="text-xs px-2 py-0.5 rounded {{ $highlight ? 'bg-blue-600 text-white font-semibold' : 'bg-blue-100 text-blue-800' }}"
                                                        @if ($highlight) title="You can teach this" @endif>
                                                        {{ $skill->name }}
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-gray-500">Nothing yet</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 pt-3 border-t border-white/50">
                                    @if ($isPending)
                                        <p class="w-full bg-white/60 text-emerald-700 text-sm font-semibold py-2 px-3 rounded text-center">
                                            ✅ Request sent
                                        </p>
                                    @elseif ($canSendRequest)
                                        <form action="{{ route('swap.request.store') }}" method="POST" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="receiver_id" value="{{ $partner->id }}">
                                            <input type="hidden" name="offered_skill_id" value="{{ $suggestion['teach'] }}">
                                            <input type="hidden" name="requested_skill_id" value="{{ $suggestion['learn'] }}">
                                            <p class="text-xs text-gray-600">
                                                You learn <span class="font-semibold">{{ $allSkills->firstWhere('id', $suggestion['learn'])?->name }}</span>,
                                                you teach <span class="font-semibold">{{ $allSkills->firstWhere('id', $suggestion['teach'])?->name }}</span>
                                            </p>
                                            <textarea name="initial_message" rows="2" maxlength="1000" placeholder="Say hi (optional)" class="w-full glass-input"></textarea>
                                            <button type="submit" class="w-full bg-emerald-600 btn-pop hover:bg-emerald-700 text-white text-sm font-semibold py-2 px-3 rounded text-center transition">
                                                🤝 Request Skill Swap
                                            </button>
                                        </form>
                                    @endif

                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        <a href="{{ route('messages.show', $partner) }}"
                                            class="block bg-white/70 hover:bg-white text-emerald-700 text-sm font-semibold py-2 px-3 rounded text-center transition">
                                            💬 Message
                                        </a>
                                        <a href="{{ route('users.show', $partner) }}"
                                            class="block bg-white/70 hover:bg-white text-gray-700 text-sm font-semibold py-2 px-3 rounded text-center transition">
                                            Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($people->hasPages())
                        <div class="mt-6 flex items-center justify-between text-sm">
                            @if ($people->onFirstPage())
                                <span class="text-gray-400">← Previous</span>
                            @else
                                <a href="{{ $people->previousPageUrl() }}" class="font-semibold text-emerald-700 hover:underline">← Previous</a>
                            @endif
                            <span class="text-gray-600">Page {{ $people->currentPage() }} of {{ $people->lastPage() }}</span>
                            @if ($people->hasMorePages())
                                <a href="{{ $people->nextPageUrl() }}" class="font-semibold text-emerald-700 hover:underline">Next →</a>
                            @else
                                <span class="text-gray-400">Next →</span>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="text-center py-12 text-gray-500">
                        @if ($isFiltered)
                            <p class="text-base">Nobody matches these filters yet.</p>
                            <p class="text-sm mt-1">Try a different skill, or <a href="{{ route('matches.index') }}" class="text-emerald-700 hover:underline">see everyone</a>.</p>
                        @else
                            <p class="text-base">No one else has added skills yet. Invite a friend!</p>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
