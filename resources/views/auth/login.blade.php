@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-12 glass p-8 rounded-2xl animate-pop-in">
    <h1 class="text-2xl font-bold mb-6 text-center">Log in</h1>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="mt-1 w-full glass-input">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" type="password" name="password" required
                   class="mt-1 w-full glass-input">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="size-5 rounded"> Remember me
        </label>

        <button type="submit" class="w-full min-h-12 bg-emerald-600 btn-pop text-white py-3 rounded-xl hover:bg-emerald-700 font-medium">
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        No account? <a href="{{ route('register') }}" class="text-emerald-600 hover:underline">Register</a>
    </p>
</div>
@endsection
