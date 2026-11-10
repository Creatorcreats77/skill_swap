@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-12 glass p-8 rounded-2xl animate-pop-in">
    <h1 class="text-2xl font-bold mb-6 text-center">Create an account</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="mt-1 w-full rounded-lg glass-input px-3 py-2 focus:border-emerald-500 focus:outline-none">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   class="mt-1 w-full rounded-lg glass-input px-3 py-2 focus:border-emerald-500 focus:outline-none">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" type="password" name="password" required
                   class="mt-1 w-full rounded-lg glass-input px-3 py-2 focus:border-emerald-500 focus:outline-none">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   class="mt-1 w-full rounded-lg glass-input px-3 py-2 focus:border-emerald-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full bg-emerald-600 btn-pop text-white py-2 rounded-md hover:bg-emerald-700 font-medium">
            Register
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Already registered? <a href="{{ route('login') }}" class="text-emerald-600 hover:underline">Log in</a>
    </p>
</div>
@endsection
