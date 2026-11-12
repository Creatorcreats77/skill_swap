<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SkillSwap') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="font-sans antialiased text-gray-900 min-h-screen bg-gradient-to-br from-emerald-200 via-green-100 to-teal-200 text-base">

    <!-- Background blobs (give the glass something to blur) -->
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-emerald-400/40 blur-3xl animate-blob"></div>
        <div
            class="absolute top-1/3 -right-24 h-[28rem] w-[28rem] rounded-full bg-lime-300/40 blur-3xl animate-blob [animation-delay:-6s]">
        </div>
        <div
            class="absolute -bottom-32 left-1/4 h-96 w-96 rounded-full bg-teal-300/40 blur-3xl animate-blob [animation-delay:-12s]">
        </div>
    </div>

    <div class="min-h-screen flex flex-col">

        <!-- Navigation Bar -->
        <nav class="sticky top-0 z-50 px-4 pt-4 sm:px-6 lg:px-8">
            <div class="glass max-w-7xl mx-auto rounded-2xl px-4 sm:px-6 animate-slide-down">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-8">
                        <a href="{{ route('matches.index') }}"
                            class="group font-bold text-xl bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                            <span
                                class="inline-block transition-transform duration-700 group-hover:rotate-180">🔄</span>
                            SkillSwap
                        </a>

                        @auth
                        <div class="hidden space-x-2 sm:flex">
                            @include('layouts.partials.nav-links')
                        </div>
                        @endauth
                    </div>

                    <div class="flex items-center space-x-3">
                        @auth
                        <a href="{{ route('profile.edit') }}" title="My profile"
                            class="flex items-center gap-2 rounded-full pr-2 hover:bg-white/40 transition {{ request()->routeIs('profile.edit') ? 'bg-white/60' : '' }}">
                            <x-avatar :user="Auth::user()" size="sm" />
                            <span class="hidden sm:inline text-sm font-medium text-gray-800">{{ Auth::user()->name }}</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-600 hover:bg-white/50 transition">
                                Logout
                            </button>
                        </form>
                        @else
                        <a href="{{ route('login') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-700 hover:bg-white/40 transition">Log
                            in</a>
                        <a href="{{ route('register') }}"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-emerald-600/90 hover:bg-emerald-700 shadow transition">Register</a>
                        @endauth
                    </div>
                </div>

                {{-- Mobile nav links --}}
                @auth
                <div class="flex flex-wrap gap-2 pb-3 sm:hidden">
                    @include('layouts.partials.nav-links')
                </div>
                @endauth
            </div>
        </nav>


        <!-- Page Header Section -->
        @hasSection('header')
        <header class="px-4 pt-6 sm:px-6 lg:px-8">
            <div class="glass max-w-7xl mx-auto rounded-2xl py-6 px-4 sm:px-6 animate-fade-up">
                @yield('header')
            </div>
        </header>
        @endif

        <!-- Main Content Section -->
        <main class="flex-grow">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="px-4 pb-4 sm:px-6 lg:px-8">
            <div class="glass-soft max-w-7xl mx-auto rounded-2xl py-4 text-center text-sm text-gray-600">
                &copy; {{ date('Y') }} SkillSwap Platform. Trade what you know, learn what you love.
            </div>
        </footer>

    </div>
</body>

</html>