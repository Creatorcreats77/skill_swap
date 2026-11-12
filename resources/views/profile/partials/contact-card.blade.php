{{-- Contact details for $user. Pass $canSeeContact = whether the viewer is allowed to see them. --}}
<div class="glass-soft rounded-xl p-4">
    <h4 class="text-sm font-bold text-gray-800 mb-2">📇 How to contact {{ $user->name }}</h4>

    @if ($canSeeContact)
        @forelse ($user->contactMethods() as $method)
            <a href="{{ $method['url'] }}" @if (str_starts_with($method['url'], 'http')) target="_blank" rel="noopener" @endif
                class="flex items-center justify-between gap-3 py-2 border-b border-white/50 last:border-0 text-sm hover:text-emerald-700">
                <span class="text-gray-500">{{ $method['label'] }}</span>
                <span class="font-semibold text-gray-800 break-all text-right">{{ $method['value'] }}</span>
            </a>
        @empty
            <p class="text-sm text-gray-500">{{ $user->name }} hasn't added any contact details yet. Use the chat instead.</p>
        @endforelse
    @else
        <p class="text-sm text-gray-600">
            🔒 Contact details are shared once a skill swap between you is accepted.
        </p>
    @endif
</div>
