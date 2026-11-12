@php
    $statusClasses = [
        'pending' => 'bg-amber-100 text-amber-800',
        'accepted' => 'bg-emerald-100 text-emerald-800',
        'declined' => 'bg-red-100 text-red-700',
        'completed' => 'bg-sky-100 text-sky-800',
    ];
@endphp
<span class="text-xs font-semibold px-2 py-0.5 rounded {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($status) }}</span>
