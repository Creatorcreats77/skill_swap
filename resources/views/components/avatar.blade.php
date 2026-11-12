@props(['user', 'size' => 'md'])

@php
    $sizeClasses = [
        'sm' => 'size-8 text-xs',
        'md' => 'size-12 text-base',
        'lg' => 'size-20 text-2xl',
        'xl' => 'size-28 text-4xl',
    ][$size] ?? 'size-12 text-base';
@endphp

@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}"
        {{ $attributes->merge(['class' => "$sizeClasses shrink-0 rounded-full object-cover ring-2 ring-white/70 shadow-sm"]) }}>
@else
    <div aria-hidden="true"
        {{ $attributes->merge(['class' => "$sizeClasses shrink-0 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 text-white font-bold flex items-center justify-center ring-2 ring-white/70 shadow-sm"]) }}>
        {{ $user->initials() }}
    </div>
@endif
