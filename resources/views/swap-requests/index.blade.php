@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
    {{ __('Swap Requests') }}
</h2>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @include('components.success')
        @include('components.error')

        @foreach ([
            ['title' => '📥 Received', 'requests' => $received, 'incoming' => true],
            ['title' => '📤 Sent', 'requests' => $sent, 'incoming' => false],
        ] as $section)
            <section class="glass rounded-2xl p-6 animate-fade-up">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $section['title'] }}</h3>

                @forelse ($section['requests'] as $swapRequest)
                    @php
                        $other = $section['incoming'] ? $swapRequest->sender : $swapRequest->receiver;
                        // From the viewer's point of view: what they learn and what they teach.
                        $youLearn = $section['incoming'] ? $swapRequest->offeredSkill : $swapRequest->requestedSkill;
                        $youTeach = $section['incoming'] ? $swapRequest->requestedSkill : $swapRequest->offeredSkill;
                    @endphp

                    <div class="glass-soft rounded-xl p-4 mb-3 last:mb-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-800">{{ $other->name }}</span>
                                @include('swap-requests.partials.status-badge', ['status' => $swapRequest->status])
                            </div>
                            <p class="text-sm text-gray-700 mt-1">
                                You learn <span class="font-semibold text-green-700">{{ $youLearn->name }}</span>
                                · you teach <span class="font-semibold text-blue-700">{{ $youTeach->name }}</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $swapRequest->created_at->diffForHumans() }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @include('swap-requests.partials.actions', ['swapRequest' => $swapRequest])
                            <a href="{{ route('swap.request.show', $swapRequest) }}"
                                class="text-sm font-semibold text-emerald-700 hover:underline px-2">Open chat →</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">
                        @if ($section['incoming'])
                            No requests yet. Make sure your skills are up to date so people can find you.
                        @else
                            You haven't sent any requests. <a href="{{ route('matches.index') }}" class="text-emerald-600 hover:underline">Find a match</a>.
                        @endif
                    </p>
                @endforelse
            </section>
        @endforeach

    </div>
</div>
@endsection
