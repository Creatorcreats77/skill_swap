@php
    $navLinks = [
        ['route' => 'matches.index', 'active' => 'matches.*', 'label' => 'Find Matches'],
        ['route' => 'profile.skills.index', 'active' => 'profile.skills.*', 'label' => 'My Skills'],
        ['route' => 'swap.request.index', 'active' => 'swap.request.*', 'label' => 'Requests'],
    ];

    $pendingCount = auth()->user()->receivedSwapRequests()->where('status', 'pending')->count();
@endphp

@foreach ($navLinks as $link)
    <a href="{{ route($link['route']) }}"
        class="px-3 py-1.5 rounded-lg text-sm transition {{ request()->routeIs($link['active']) ? 'bg-white/60 text-emerald-700 font-semibold shadow-sm' : 'text-gray-700 hover:bg-white/40' }}">
        {{ $link['label'] }}
        @if ($link['route'] === 'swap.request.index' && $pendingCount > 0)
            <span class="ml-1 inline-flex items-center justify-center rounded-full bg-emerald-600 px-1.5 text-xs font-bold text-white">{{ $pendingCount }}</span>
        @endif
    </a>
@endforeach
