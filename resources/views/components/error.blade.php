@if ($errors->any())
<div class="animate-pop-in bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
    <ul class="list-disc list-inside text-sm">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif