@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    {{ __('Add a New Skill') }}
</h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @include('components.success')
        @include('components.error')

        <div class="glass rounded-2xl p-6 animate-fade-up">
            <h3 class="text-lg font-medium text-gray-900 mb-1">➕ Create a new skill item</h3>
            <p class="text-sm text-gray-600 mb-4">Can't find your skill in the database? Add it here.</p>

            <form action="{{ route('profile.skills.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm text-gray-700 mb-1">Skill name</label>
                    <input type="text" id="name" name="name" required value="{{ old('name') }}" maxlength="60"
                        placeholder="e.g. Guitar, Laravel, Spanish"
                        class="glass-input w-full">
                </div>

                <div>
                    <label for="category" class="block text-sm text-gray-700 mb-1">Category</label>
                    <input type="text" id="category" name="category" value="{{ old('category') }}" list="category-list" maxlength="60"
                        placeholder="Programming"
                        class="glass-input w-full">
                    <datalist id="category-list">
                        @foreach ($categories ?? [] as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">I want to…</label>
                    <select name="type" id="new-skill-type"
                        class="glass-input w-full">
                        <option value="offered" @selected(old('type')==='offered')>Teach it</option>
                        <option value="wanted" @selected(old('type')==='wanted')>Learn it</option>
                        <option value="both" @selected(old('type')==='both')>Both</option>
                    </select>
                </div>

                <div id="new-skill-level">
                    <label class="block text-sm text-gray-700 mb-1">My level</label>
                    <select name="proficiency"
                        class="glass-input w-full">
                        <option value="">—</option>
                        <option value="beginner" @selected(old('proficiency') === 'beginner')>Beginner</option>
                        <option value="intermediate" @selected(old('proficiency') === 'intermediate')>Intermediate</option>
                        <option value="expert" @selected(old('proficiency') === 'expert')>Expert</option>
                    </select>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <a href="{{ route('profile.skills.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    <button type="submit"
                        class="bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded shadow">
                        Add skill
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    // Hide the "My level" field when the new skill is learn-only
    const typeSel = document.getElementById('new-skill-type');
    const levelBox = document.getElementById('new-skill-level');
    const sync = () => levelBox.style.display = typeSel.value === 'wanted' ? 'none' : '';
    typeSel.addEventListener('change', sync);
    sync();
</script>
@endsection