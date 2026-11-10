@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Manage Your Skills') }}
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="glass overflow-hidden rounded-2xl p-6 animate-fade-up">
                
                @if (session('success'))
                    <div class="animate-pop-in mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('profile.skills.update') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Skills I Can Teach -->
                        <div class="glass-soft p-4 rounded-xl hover-lift">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">🛠️ Skills I Can Teach</h3>
                            <p class="text-sm text-gray-600 mb-4">Select the topics you are confident in sharing with others.</p>
                            
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @foreach($allSkills as $skill)
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="offered_skills[]" value="{{ $skill->id }}"
                                            {{ in_array($skill->id, $userOfferedIds) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                        <span class="text-gray-700">{{ $skill->name }} <span class="text-xs text-gray-400">({{ $skill->category }})</span></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Skills I Want to Learn -->
                        <div class="glass-soft p-4 rounded-xl hover-lift">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">🎯 Skills I Want to Learn</h3>
                            <p class="text-sm text-gray-600 mb-4">Select the topics you're looking to gain knowledge in.</p>
                            
                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                @foreach($allSkills as $skill)
                                    <label class="flex items-center space-x-3">
                                        <input type="checkbox" name="wanted_skills[]" value="{{ $skill->id }}"
                                            {{ in_array($skill->id, $userWantedIds) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                        <span class="text-gray-700">{{ $skill->name }} <span class="text-xs text-gray-400">({{ $skill->category }})</span></span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded shadow">
                            Save Skills
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection