{{-- V4-P6: notification bell (polls /notifications/feed every 60s). --}}
<div class="relative" x-data="notifBell()" x-init="load(); setInterval(() => load(), 60000)" @click.outside="open = false">
    <button type="button" @click="open = !open; if (open) load()"
            class="relative rounded-md px-2 py-2 text-gray-200 hover:text-white hover:bg-gray-700 focus:outline-none" aria-label="Notifications">
        <span class="text-lg">🔔</span>
        <span x-show="unread > 0" x-text="unread > 99 ? '99+' : unread" x-cloak
              class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] rounded-full bg-red-600 px-1 text-center text-[10px] font-bold leading-[1.1rem] text-white"></span>
    </button>

    <div x-show="open" x-cloak
         class="absolute right-0 z-[9998] mt-2 w-80 max-w-[90vw] rounded-md bg-white text-gray-800 shadow-lg ring-1 ring-black/10">
        <div class="flex items-center justify-between border-b px-3 py-2 text-sm">
            <span class="font-semibold">Notifications</span>
            <button type="button" @click="markAll()" class="text-xs text-blue-600 hover:underline">Mark all read</button>
        </div>
        <div class="max-h-96 overflow-y-auto divide-y">
            <template x-for="n in items" :key="n.id">
                <a :href="n.url || '#'" @click="markOne(n)" class="block px-3 py-2 text-sm hover:bg-gray-50" :class="n.read ? 'opacity-60' : ''">
                    <div class="font-medium" x-text="n.title"></div>
                    <div class="text-xs text-gray-600" x-show="n.body" x-text="n.body"></div>
                    <div class="text-[11px] text-gray-400" x-text="n.when"></div>
                </a>
            </template>
            <div x-show="items.length === 0" class="px-3 py-4 text-center text-sm text-gray-500">No notifications yet.</div>
        </div>
        <a href="{{ route('notifications.index') }}" class="block border-t px-3 py-2 text-center text-xs text-blue-600 hover:bg-gray-50">View all</a>
    </div>
</div>
@once
<script>
    function notifBell() {
        const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
        return {
            open: false, unread: 0, items: [],
            async load() {
                try {
                    const r = await fetch(@js(route('notifications.feed')), {headers: {'Accept': 'application/json'}});
                    if (!r.ok) return;
                    const d = await r.json();
                    this.unread = d.unread; this.items = d.items;
                } catch (e) {}
            },
            markOne(n) {
                if (n.read) return;
                fetch(@js(url('/notifications')) + '/' + n.id + '/read', {method: 'POST', keepalive: true,
                    headers: {'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json'}});
            },
            async markAll() {
                await fetch(@js(route('notifications.readAll')), {method: 'POST',
                    headers: {'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json'}});
                this.load();
            },
        };
    }
</script>
@endonce
