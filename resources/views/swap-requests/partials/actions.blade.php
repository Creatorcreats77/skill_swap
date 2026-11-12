{{-- Status buttons for a swap request, shown to whichever participant is allowed to use them. --}}
@php
    $isReceiver = (int) $swapRequest->receiver_id === auth()->id();
    $buttons = [];

    if ($swapRequest->status === 'pending' && $isReceiver) {
        $buttons[] = ['status' => 'accepted', 'label' => 'Accept', 'class' => 'bg-emerald-600 hover:bg-emerald-700 text-white'];
        $buttons[] = ['status' => 'declined', 'label' => 'Decline', 'class' => 'bg-white/70 hover:bg-white text-red-600'];
    } elseif ($swapRequest->status === 'pending') {
        $buttons[] = ['status' => 'declined', 'label' => 'Cancel request', 'class' => 'bg-white/70 hover:bg-white text-red-600'];
    } elseif ($swapRequest->status === 'accepted') {
        $buttons[] = ['status' => 'completed', 'label' => 'Mark completed', 'class' => 'bg-sky-600 hover:bg-sky-700 text-white'];
    }
@endphp

@foreach ($buttons as $button)
    <form method="POST" action="{{ route('swap.request.update', $swapRequest) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="{{ $button['status'] }}">
        <button type="submit" class="btn-pop text-sm font-semibold py-1.5 px-3 rounded shadow {{ $button['class'] }}">
            {{ $button['label'] }}
        </button>
    </form>
@endforeach
