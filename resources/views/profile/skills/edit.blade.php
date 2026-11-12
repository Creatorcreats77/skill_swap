@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    {{ __('My Skills') }}
</h2>
@endsection

@section('content')
@php
    $offeredIds = array_map('intval', old('offered_skills', $userOfferedIds));
    $wantedIds  = array_map('intval', old('wanted_skills', $userWantedIds));
    $grouped    = $allSkills->groupBy(fn ($s) => $s->category ?: 'Other');
@endphp

<div class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @include('components.success')
        @include('components.error')

        <div class="glass rounded-2xl p-6 animate-fade-up">
            <form action="{{ route('profile.skills.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Filters --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" id="skill-search" placeholder="Search skills..."
                        class="glass-input w-full">

                    <select id="category-filter"
                        class="glass-input">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>

                    <a href="{{ route('profile.skills.create') }}"
                        class="whitespace-nowrap text-center min-h-12 bg-white/70 hover:bg-white text-gray-700 font-semibold py-3 px-4 rounded-xl">
                        + New skill
                    </a>
                </div>

                {{-- Legend --}}
                <div class="hidden sm:grid grid-cols-12 gap-2 px-3 text-xs font-semibold text-gray-500 uppercase">
                    <div class="col-span-5">Skill</div>
                    <div class="col-span-2 text-center">I offer</div>
                    <div class="col-span-2 text-center">I want</div>
                    <div class="col-span-3">Level</div>
                </div>

                {{-- Skills grouped by category --}}
                @forelse ($grouped as $category => $skills)
                    <div class="category-group" data-category="{{ $category }}">
                        <h3 class="text-sm font-bold text-emerald-700 mb-2">{{ $category }}</h3>

                        <div class="divide-y divide-gray-100 border border-gray-100 rounded-lg">
                            @foreach ($skills as $skill)
                                @php
                                    $isOffered = in_array($skill->id, $offeredIds, true);
                                    $isWanted  = in_array($skill->id, $wantedIds, true);
                                    $level     = old("proficiency.{$skill->id}", $userProficiency[$skill->id] ?? '');
                                @endphp

                                <div class="skill-row grid grid-cols-12 gap-2 items-center px-3 py-2"
                                    data-name="{{ Str::lower($skill->name) }}"
                                    data-category="{{ $category }}">

                                    <div class="col-span-12 sm:col-span-5 text-sm text-gray-800">
                                        {{ $skill->name }}
                                    </div>

                                    <label class="col-span-4 sm:col-span-2 flex sm:justify-center items-center gap-2 text-sm text-gray-600">
                                        <input type="checkbox" name="offered_skills[]" value="{{ $skill->id }}"
                                            class="offer-checkbox size-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            @checked($isOffered)>
                                        <span class="sm:hidden">Offer</span>
                                    </label>

                                    <label class="col-span-4 sm:col-span-2 flex sm:justify-center items-center gap-2 text-sm text-gray-600">
                                        <input type="checkbox" name="wanted_skills[]" value="{{ $skill->id }}"
                                            class="size-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            @checked($isWanted)>
                                        <span class="sm:hidden">Want</span>
                                    </label>

                                    <div class="col-span-4 sm:col-span-3">
                                        <select name="proficiency[{{ $skill->id }}]"
                                            class="proficiency-select glass-input w-full"
                                            @disabled(! $isOffered)>
                                            <option value="">Level</option>
                                            @foreach (['beginner', 'intermediate', 'expert'] as $option)
                                                <option value="{{ $option }}" @selected($level === $option)>
                                                    {{ ucfirst($option) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-6">
                        No skills yet.
                        <a href="{{ route('profile.skills.create') }}" class="text-emerald-600 hover:underline">Add the first one</a>.
                    </p>
                @endforelse

                <p id="no-results" class="hidden text-sm text-gray-500 text-center py-6">No skills match your filter.</p>

                <div class="flex justify-between items-center pt-4">
                    <a href="{{ route('profile.skills.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    <button type="submit"
                        class="bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded shadow">
                        Save skills
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const search   = document.getElementById('skill-search');
        const category = document.getElementById('category-filter');
        const rows     = document.querySelectorAll('.skill-row');
        const groups   = document.querySelectorAll('.category-group');
        const empty    = document.getElementById('no-results');

        // Proficiency is only enabled while "I offer" is checked
        rows.forEach(row => {
            const offer  = row.querySelector('.offer-checkbox');
            const select = row.querySelector('.proficiency-select');
            offer.addEventListener('change', () => {
                select.disabled = !offer.checked;
                if (!offer.checked) select.value = '';
            });
        });

        function applyFilters() {
            const q = search.value.trim().toLowerCase();
            const c = category.value;
            let visible = 0;

            rows.forEach(row => {
                const show = (!q || row.dataset.name.includes(q)) &&
                             (!c || row.dataset.category === c);
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            groups.forEach(g => {
                g.classList.toggle('hidden', !g.querySelector('.skill-row:not(.hidden)'));
            });

            empty.classList.toggle('hidden', visible > 0);
        }

        search.addEventListener('input', applyFilters);
        category.addEventListener('change', applyFilters);
    });
</script>
@endsection