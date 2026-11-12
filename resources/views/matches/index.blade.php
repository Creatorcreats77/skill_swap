@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Find Skill Swap Partners') }}
    </h2>
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
                    so other people can find you too.
                </div>
            @endunless

            <!-- Search Filter Bar -->
            <div class="glass overflow-hidden rounded-2xl p-6 animate-fade-up">
                <form method="GET" action="{{ route('matches.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    
                    <!-- What I Want to Learn -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">I Want to Learn:</label>
                        <select name="wanted_skill_id" required class="w-full glass-input">
                            <option value="">-- Choose skill you want --</option>
                            @foreach($allSkills as $skill)
                                <option value="{{ $skill->id }}" @selected($wantedSkillId === $skill->id)>
                                    {{ $skill->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- What I Can Teach -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">I Can Teach In Return:</label>
                        <select name="offered_skill_id" required class="w-full glass-input">
                            <option value="">-- Choose skill you offer --</option>
                            @foreach($allSkills as $skill)
                                <option value="{{ $skill->id }}" @selected($offeredSkillId === $skill->id)>
                                    {{ $skill->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="w-full min-h-12 bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow">
                            Search Matches 🔍
                        </button>
                    </div>

                </form>
            </div>

            <!-- Search Results Section -->
            <div class="glass overflow-hidden rounded-2xl p-6 animate-fade-up [animation-delay:0.15s]">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Available Exchange Partners</h3>

                @if($matches->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 stagger">
                        @foreach($matches as $partner)
                            <div class="glass-soft rounded-xl p-5 flex flex-col justify-between shadow-sm hover-lift">
                                <div>
                                    <div class="flex items-center space-x-3 mb-3">
                                        <div class="h-10 w-10 rounded-full bg-emerald-200 flex items-center justify-center font-bold text-emerald-700">
                                            {{ mb_strtoupper(mb_substr($partner->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800">{{ $partner->name }}</h4>
                                            <p class="text-xs text-gray-500">{{ $partner->bio ?? 'Ready to swap skills!' }}</p>
                                        </div>
                                    </div>

                                    <div class="border-t border-white/50 pt-3 space-y-2 text-sm">
                                        <div>
                                            <span class="font-semibold text-green-600">They Teach:</span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($partner->offeredSkills as $skill)
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded">{{ $skill->name }}</span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div>
                                            <span class="font-semibold text-blue-600">They Want:</span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($partner->wantedSkills as $skill)
                                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded">{{ $skill->name }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 pt-3 border-t border-white/50">
                                    @if (in_array($partner->id, $pendingReceiverIds, true))
                                        <p class="w-full bg-white/60 text-emerald-700 text-sm font-semibold py-2 px-3 rounded text-center">
                                            ✅ Request sent
                                        </p>
                                    @else
                                        <form action="{{ route('swap.request.store') }}" method="POST" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="receiver_id" value="{{ $partner->id }}">
                                            <input type="hidden" name="offered_skill_id" value="{{ $offeredSkillId }}">
                                            <input type="hidden" name="requested_skill_id" value="{{ $wantedSkillId }}">
                                            <textarea name="initial_message" rows="2" maxlength="1000"
                                                placeholder="Say hi (optional)"
                                                class="w-full glass-input"></textarea>

                                            <button type="submit" class="w-full bg-emerald-600 btn-pop hover:bg-emerald-700 text-white text-sm font-semibold py-2 px-3 rounded text-center transition">
                                                🤝 Request Skill Swap
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        @if ($wantedSkillId && $offeredSkillId)
                            <p class="text-base">No matching partners found yet.</p>
                            <p class="text-sm mt-1">Try selecting a different combination of skills you want to learn and teach!</p>
                        @else
                            <p class="text-base">Pick a skill you want to learn and one you can teach to find partners.</p>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection