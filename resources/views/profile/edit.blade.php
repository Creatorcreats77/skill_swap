@extends('layouts.app')

@section('header')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('My Profile') }}
    </h2>
    <a href="{{ route('users.show', $user) }}" class="text-sm font-semibold text-emerald-700 hover:underline">
        See how others see you →
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @include('components.success')
        @include('components.error')

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
            class="glass rounded-2xl p-6 space-y-8 animate-fade-up">
            @csrf
            @method('PUT')

            {{-- Photo --}}
            <section>
                <h3 class="text-lg font-medium text-gray-900 mb-4">📷 Photo</h3>
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    <div id="avatar-preview-wrap">
                        <x-avatar :user="$user" size="xl" />
                    </div>
                    <img id="avatar-preview" alt="" class="hidden size-28 rounded-full object-cover ring-2 ring-white/70 shadow-sm">

                    <div class="space-y-2">
                        <label for="avatar"
                            class="inline-flex items-center min-h-12 cursor-pointer bg-white/70 hover:bg-white text-emerald-700 font-semibold py-3 px-4 rounded-xl shadow">
                            Choose a photo
                        </label>
                        <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only">
                        <p class="text-xs text-gray-600">JPG, PNG, WEBP or GIF, up to 2 MB.</p>

                        @if ($user->avatar)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="remove_avatar" value="0">
                                <input type="checkbox" name="remove_avatar" value="1" class="size-5 rounded"> Remove current photo
                            </label>
                        @endif
                    </div>
                </div>
            </section>

            {{-- About --}}
            <section class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">🙋 About you</h3>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255"
                        class="w-full glass-input">
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Short bio</label>
                    <textarea id="bio" name="bio" rows="3" maxlength="500" placeholder="A sentence or two about you and how you like to learn."
                        class="w-full glass-input">{{ old('bio', $user->bio) }}</textarea>
                </div>
            </section>

            {{-- Contact --}}
            <section class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">📇 Contact</h3>
                    <p class="text-sm text-gray-600">Only shown to people once you've accepted a skill swap with them.</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="show_email" value="0">
                    <input type="checkbox" name="show_email" value="1" class="size-5 rounded" @checked(old('show_email', $user->show_email))>
                    Share my email (<span class="font-medium">{{ $user->email }}</span>)
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone / WhatsApp</label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30"
                            placeholder="+993 61 234567" class="w-full glass-input">
                    </div>
                    <div>
                        <label for="telegram" class="block text-sm font-medium text-gray-700 mb-1">Telegram</label>
                        <input id="telegram" type="text" name="telegram" value="{{ old('telegram', $user->telegram) }}" maxlength="64"
                            placeholder="@username" class="w-full glass-input">
                    </div>
                    <div>
                        <label for="instagram" class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                        <input id="instagram" type="text" name="instagram" value="{{ old('instagram', $user->instagram) }}" maxlength="64"
                            placeholder="@username" class="w-full glass-input">
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit"
                    class="min-h-12 bg-emerald-600 btn-pop hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-xl shadow">
                    Save profile
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Preview the chosen photo before saving.
    document.getElementById('avatar').addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;
        const preview = document.getElementById('avatar-preview');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        document.getElementById('avatar-preview-wrap').classList.add('hidden');
    });
</script>
@endsection
