@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    {{ $skill->name }}
</h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="glass rounded-2xl p-6 space-y-4 animate-fade-up">
            <div>
                <span class="text-xs uppercase tracking-wider text-gray-500 font-bold">Category</span>
                <p class="text-gray-700">{{ $skill->category ?? 'Uncategorized' }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-xs uppercase tracking-wider text-gray-500 font-bold">Who teaches it</span>
                    <ul class="mt-1 space-y-1 text-sm text-gray-700">
                        @forelse ($offerers as $person)
                            <li>
                                {{ $person->name }}
                                @if ($person->pivot->proficiency)
                                    <span class="text-xs text-gray-500">· {{ ucfirst($person->pivot->proficiency) }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="text-gray-500">Nobody else yet.</li>
                        @endforelse
                    </ul>
                </div>

                <div>
                    <span class="text-xs uppercase tracking-wider text-gray-500 font-bold">Who wants to learn it</span>
                    <ul class="mt-1 space-y-1 text-sm text-gray-700">
                        @forelse ($seekers as $person)
                            <li>{{ $person->name }}</li>
                        @empty
                            <li class="text-gray-500">Nobody else yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-4">
                <a href="{{ route('matches.index', ['wanted_skill_id' => $skill->id]) }}"
                    class="bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded shadow">
                    Find a teacher
                </a>
                <a href="{{ route('profile.skills.edit') }}"
                    class="bg-white/70 hover:bg-white text-emerald-700 font-bold py-2 px-4 rounded shadow">
                    Edit my skills
                </a>
                <a href="{{ route('profile.skills.index') }}"
                    class="text-gray-700 hover:underline font-medium py-2 px-2">
                    Back to my skills
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
