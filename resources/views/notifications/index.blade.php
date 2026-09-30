@extends('layouts.default')

@section('content')
<div class="container mx-auto p-4 max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Notifications</h2>
        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button class="bg-gray-700 text-white px-3 py-2 rounded text-sm hover:bg-gray-800">Mark all read</button>
        </form>
    </div>

    <div class="bg-white shadow rounded divide-y">
        @forelse ($notifications as $n)
            <a href="{{ $n->url ?: '#' }}" data-nid="{{ $n->id }}"
               class="block px-4 py-3 hover:bg-gray-50 {{ isset($readIds[$n->id]) ? 'opacity-60' : '' }}">
                <div class="flex justify-between gap-3">
                    <span class="font-medium">{{ $n->title }}</span>
                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $n->created_at->timezone('Europe/London')->format('d M Y H:i') }}</span>
                </div>
                @if ($n->body)
                    <div class="text-sm text-gray-600 mt-1">{{ $n->body }}</div>
                @endif
            </a>
        @empty
            <div class="px-4 py-6 text-center text-gray-500">No notifications yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</div>
<script>
document.querySelectorAll('[data-nid]').forEach(function (a) {
    a.addEventListener('click', function () {
        fetch(@js(url('/notifications')) + '/' + a.dataset.nid + '/read', {
            method: 'POST', keepalive: true,
            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'}
        });
    });
});
</script>
@endsection
