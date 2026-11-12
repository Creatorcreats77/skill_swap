@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('My Skills') }}
    </h2>
    <div class="flex gap-2">
        <a href="{{ route('profile.skills.edit') }}"
            class="bg-white/70 hover:bg-white text-emerald-700 font-semibold py-2 px-4 rounded shadow">
            ✏️ Edit skills
        </a>
        <a href="{{ route('profile.skills.create') }}"
            class="bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded shadow">
            ➕ Add new skill
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @include('components.success')
        @include('components.error')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-up">
            @foreach ([
                ['type' => 'offered', 'title' => '🛠️ Skills I Can Teach', 'skills' => $offeredSkills, 'badge' => 'bg-green-100 text-green-800'],
                ['type' => 'wanted', 'title' => '🎯 Skills I Want to Learn', 'skills' => $wantedSkills, 'badge' => 'bg-blue-100 text-blue-800'],
            ] as $column)
                <div class="glass rounded-2xl p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $column['title'] }}</h3>

                    @forelse ($column['skills'] as $skill)
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-white/50 last:border-0">
                            <div class="min-w-0">
                                <a href="{{ route('profile.skills.show', $skill) }}"
                                    class="font-medium text-emerald-700 hover:underline">{{ $skill->name }}</a>
                                @if ($skill->category)
                                    <span class="text-xs text-gray-500">({{ $skill->category }})</span>
                                @endif
                                @if ($column['type'] === 'offered' && $skill->pivot->proficiency)
                                    <span class="ml-1 text-xs px-2 py-0.5 rounded {{ $column['badge'] }}">{{ ucfirst($skill->pivot->proficiency) }}</span>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('profile.skills.destroy', $skill) }}"
                                onsubmit="return confirm('Remove {{ e($skill->name) }} from this list?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="type" value="{{ $column['type'] }}">
                                <button type="submit" class="text-xs text-red-600 hover:underline">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">
                            Nothing here yet.
                            <a href="{{ route('profile.skills.edit') }}" class="text-emerald-600 hover:underline">Pick some skills</a>.
                        </p>
                    @endforelse
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection
