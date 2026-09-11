<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none !important}</style>

    {{-- ===== Phase 10 — GMS design system (purely presentational) =====
         Corporate ops-software identity: IBM Plex Sans, slate neutrals,
         steel-blue accent (replaces emerald), control-room table labels,
         tabular numerals, unified radii + shadows, refined inputs/buttons.
         Element + utility-level CSS only: no view markup, no Alpine. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ---- 1. Accent: emerald -> steel blue (vars + hard fallbacks) ---- */
        :root{
            --color-emerald-50:#eef3fa;  --color-emerald-100:#d9e5f3; --color-emerald-200:#b7cceb;
            --color-emerald-300:#8cabd8; --color-emerald-400:#5e86c2; --color-emerald-500:#3e69ab;
            --color-emerald-600:#2f5896; --color-emerald-700:#274877; --color-emerald-800:#223c61;
            --color-emerald-900:#20344f;
        }
        .bg-emerald-50{background-color:#eef3fa!important}
        .bg-emerald-100{background-color:#d9e5f3!important}
        .bg-emerald-600{background-color:#2f5896!important}
        .hover\:bg-emerald-100:hover{background-color:#d9e5f3!important}
        .hover\:bg-emerald-700:hover{background-color:#274877!important}
        .text-emerald-400{color:#5e86c2!important}
        .text-emerald-600{color:#2f5896!important}
        .text-emerald-700{color:#274877!important}
        .text-emerald-800{color:#223c61!important}
        .text-emerald-900{color:#20344f!important}
        .hover\:text-emerald-300:hover{color:#8cabd8!important}
        .border-emerald-200{border-color:#b7cceb!important}
        .border-emerald-300{border-color:#8cabd8!important}
        .hover\:border-emerald-500:hover{border-color:#3e69ab!important}
        .focus\:border-emerald-500:focus{border-color:#3e69ab!important}
        .focus\:ring-emerald-300:focus{--tw-ring-color:#8cabd8!important}
        .focus\:ring-emerald-500:focus{--tw-ring-color:#3e69ab!important}

        /* ---- 2. Neutrals: gray -> cool slate; header bar -> uniform navy ---- */
        :root{
            --color-gray-50:#f8fafc;  --color-gray-100:#f1f5f9; --color-gray-200:#e3e8ef;
            --color-gray-300:#cbd5e1; --color-gray-400:#94a3b8; --color-gray-500:#64748b;
            --color-gray-600:#475569; --color-gray-700:#334155; --color-gray-800:#22304a;
            --color-gray-900:#141d2e;
        }
        .bg-gray-800{background-color:#22304a!important}
        .bg-gray-900{background-color:#141d2e!important}
        .hover\:bg-gray-700:hover{background-color:#334155!important}

        /* ---- 3. Typography: IBM Plex Sans, calm ink, tighter headings ---- */
        body{
            font-family:'IBM Plex Sans',ui-sans-serif,system-ui,-apple-system,sans-serif;
            color:#26324a;
            -webkit-font-smoothing:antialiased;
            background-color:#f4f6f9;
        }
        h1,h2,h3{letter-spacing:-0.015em;color:#141d2e}
        h1{font-weight:700}
        h2,h3{font-weight:600}

        /* ---- 4. Signature: control-room table labels + ledger numerals ---- */
        table thead th{
            font-size:.6875rem;
            text-transform:uppercase;
            letter-spacing:.07em;
            font-weight:600;
            color:#5b6b84;
        }
        table td{font-variant-numeric:tabular-nums}
        table thead{border-bottom:1px solid #e3e8ef}

        /* ---- 5. One radius scale, one shadow idiom ---- */
        .rounded-md{border-radius:.5rem}
        .rounded-lg{border-radius:.625rem}
        .rounded-xl{border-radius:.75rem}
        .rounded-2xl{border-radius:.875rem}
        .shadow-sm{box-shadow:0 1px 2px rgba(20,29,46,.05),0 0 0 1px rgba(20,29,46,.03)}
        .shadow{box-shadow:0 1px 2px rgba(20,29,46,.06),0 4px 12px -4px rgba(20,29,46,.08)}
        .shadow-md,.shadow-lg{box-shadow:0 2px 4px rgba(20,29,46,.06),0 10px 24px -8px rgba(20,29,46,.12)}

        /* ---- 6. Inputs: consistent, calm, accent focus (radios/checkboxes untouched) ---- */
        input:not([type=checkbox]):not([type=radio]):not([type=file]),
        select,textarea{
            border-radius:.5rem;
            border-color:#cbd5e1;
            background-color:#fff;
            transition:border-color .15s ease,box-shadow .15s ease;
        }
        input:not([type=checkbox]):not([type=radio]):not([type=file]):focus,
        select:focus,textarea:focus{
            outline:none;
            border-color:#3e69ab;
            box-shadow:0 0 0 3px rgba(62,105,171,.18);
        }
        input::placeholder,textarea::placeholder{color:#9aa7bd}

        /* ---- 7. Buttons & links: quiet physicality ---- */
        button,a{transition:background-color .15s ease,color .15s ease,border-color .15s ease,box-shadow .15s ease,transform .1s ease}
        button:active{transform:translateY(.5px)}
        :focus-visible{outline:2px solid #3e69ab;outline-offset:2px}

        /* ---- 8. Small polish ---- */
        ::selection{background:#d9e5f3;color:#141d2e}
        table tbody tr{transition:background-color .12s ease}
    </style>

    <link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIA...">
    <title>@yield('title', 'Security Guard')</title>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="min-h-screen flex flex-col">

<!-- Navbar / Header -->
<header class="bg-gray-800 text-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
         x-data="{ mobileOpen: false }">

        <div class="flex items-center justify-between h-16">

            <!-- Left: Logo + Brand -->
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    <img
                        class="h-10 w-10 sm:h-12 sm:w-12 object-contain"
                        src="{{ asset('secure premises.png') }}"
                        alt="Secure Premises"
                    >
                    <div class="hidden sm:block leading-tight">
                        <div class="font-semibold text-white group-hover:text-gray-100">Secure Premises</div>
                        <div class="text-xs text-gray-300">Guard Management System</div>
                    </div>
                </a>
            </div>

            <!-- Center: Desktop Menu -->
            <nav class="hidden md:flex items-center gap-2">
                @php
                    $navBase = 'px-3 py-2 rounded-md text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-gray-800';
                    $active  = 'bg-gray-900 text-white';
                    $idle    = 'text-gray-200 hover:bg-gray-700 hover:text-white';
                @endphp

                <a href="{{ route('home') }}"
                   class="{{ $navBase }} {{ request()->routeIs('home') ? $active : $idle }}">
                    Home
                </a>

                <a href="{{ route('security-guards.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('security-guards.*') ? $active : $idle }}">
                    Guards
                </a>

                @can('clients.view')
                <a href="{{ route('clients.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('clients.*') ? $active : $idle }}">
                    Clients
                </a>
                @endcan

                <a href="{{ route('events.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('events.*') ? $active : $idle }}">
                    Events
                </a>
@can('view-reports')
    <a href="{{ route('reports.home') }}"
       class="{{ $navBase }} {{ request()->routeIs('reports.*') ? $active : $idle }}">
        Reports
    </a>
@endcan
@can('manage-invoices')
    <a href="{{ route('invoices.index') }}"
       class="px-3 py-2 rounded-md transition
              {{ request()->routeIs('invoices.*') ? 'bg-gray-900 text-white' : 'hover:bg-gray-700 text-white' }}">
       Client Invoices
    </a>
@endcan

@can('manage-invoices')
<a href="{{ route('guard-invoices.index') }}"
   class="px-3 py-2 rounded-md transition {{ request()->routeIs('guard-invoices.*') ? 'bg-gray-900 text-white' : 'hover:bg-gray-700' }}">
    Guard Invoices
</a>
@endcan

<a href="{{ route('chaseup.index') }}"
   class="{{ $navBase }} {{ request()->routeIs('chaseup.*') ? $active : $idle }}">
    ChaseUp
</a>



                @can('view-executive')
                    <a href="{{ route('executive.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('executive.*') ? $active : $idle }}">
                        Executive
                    </a>
                @endcan

                @can('users.manage')
                    <a href="{{ route('users.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('users.*') ? $active : $idle }}">
                        Users
                    </a>
                @endcan
            </nav>

            <!-- Right: User + Logout (Desktop) -->
            <div class="hidden md:flex items-center gap-3">
                <div class="text-sm text-gray-200">
                    <span class="font-medium text-white">{{ Auth::user()->name }}</span>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-2 rounded-md text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-gray-800">
                        Logout
                    </button>
                </form>
            </div>

            <!-- Mobile: Menu Button -->
            <div class="md:hidden flex items-center gap-2">
                <button type="button"
                        @click="mobileOpen = !mobileOpen"
                        class="inline-flex items-center justify-center rounded-md px-3 py-2 text-gray-200 hover:text-white hover:bg-gray-700 transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-gray-800"
                        aria-label="Open menu">
                    <span class="text-xl" x-text="mobileOpen ? '✕' : '☰'"></span>
                </button>
            </div>

        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden mt-2 pb-4"
             x-show="mobileOpen"
             x-transition
             x-cloak>

            @php
                $mBase = 'block px-3 py-2 rounded-md text-sm font-medium transition';
                $mActive = 'bg-gray-900 text-white';
                $mIdle = 'text-gray-200 hover:bg-gray-700 hover:text-white';
            @endphp

            <div class="space-y-1">
                <a href="{{ route('home') }}"
                   class="{{ $mBase }} {{ request()->routeIs('home') ? $mActive : $mIdle }}">
                    Home
                </a>

                <a href="{{ route('security-guards.index') }}"
                   class="{{ $mBase }} {{ request()->routeIs('security-guards.*') ? $mActive : $mIdle }}">
                    Guards
                </a>

                @can('clients.view')
                <a href="{{ route('clients.index') }}"
                   class="{{ $mBase }} {{ request()->routeIs('clients.*') ? $mActive : $mIdle }}">
                    Clients
                </a>
                @endcan

                <a href="{{ route('events.index') }}"
                   class="{{ $mBase }} {{ request()->routeIs('events.*') ? $mActive : $mIdle }}">
                    Events
                </a>
@can('view-reports')
    <a href="{{ route('reports.home') }}"
       class="{{ $mBase }} {{ request()->routeIs('reports.*') ? $mActive : $mIdle }}">
        Reports
    </a>
@endcan

<a href="{{ route('chaseup.index') }}"
   class="{{ $mBase }} {{ request()->routeIs('chaseup.*') ? $mActive : $mIdle }}">
    ChaseUp
</a>


                @can('view-executive')
                    <a href="{{ route('executive.index') }}"
                       class="{{ $mBase }} {{ request()->routeIs('executive.*') ? $mActive : $mIdle }}">
                        Executive
                    </a>
                @endcan

                @can('users.manage')
                    <a href="{{ route('users.index') }}"
                       class="{{ $mBase }} {{ request()->routeIs('users.*') ? $mActive : $mIdle }}">
                        Users
                    </a>
                @endcan
                
                

                <div class="pt-3 mt-3 border-t border-gray-700">
                    <div class="px-3 text-xs text-gray-300 mb-2">
                        Signed in as <span class="font-medium text-white">{{ Auth::user()->name }}</span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST" class="px-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full bg-gray-700 hover:bg-gray-600 text-white px-3 py-2 rounded-md text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-gray-800">
                            Logout
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>
</header>


        <!-- Flash Messages -->
        <main class="flex-1 p-6">
            @if(session()->has('success'))
                <div class="mb-4 rounded-lg bg-green-100 border border-green-400 text-green-800 px-4 py-3 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session()->has('error'))
                <div class="mb-4 rounded-lg bg-red-100 border border-red-400 text-red-800 px-4 py-3 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-4 mt-auto">
    <div class="max-w-7xl mx-auto px-4 text-center text-sm">
        &copy; {{ date('Y') }} Developed By 
        <a href="https://www.wredd.com" target="_blank" class="text-emerald-400 hover:text-emerald-300 underline">
            WREDD
        </a>.
    </div>
</footer>
    </div>

    

    @auth
    {{-- Chaseup shift reminders: client-side poller + toasts (no cron, UK time). --}}
    <div id="shiftReminderToasts" class="fixed top-4 right-4 z-[9999] flex w-80 max-w-[90vw] flex-col gap-2"></div>
    <script>
    (function () {
        const ENDPOINT = "{{ route('chaseup.reminders') }}";
        const TIERS = [120, 90, 60];   // minutes before shift start
        const CATCH = 30;              // catch window (min) per tier
        const POLL_MS = 60000;
        const STORE_KEY = 'gms_shift_reminders_shown';

        function loadShown() {
            try { return new Set(JSON.parse(localStorage.getItem(STORE_KEY) || '[]')); }
            catch (e) { return new Set(); }
        }
        function saveShown(set) {
            try { localStorage.setItem(STORE_KEY, JSON.stringify([].concat([...set]).slice(-500))); } catch (e) {}
        }

        function showToast(r, tier) {
            const wrap = document.getElementById('shiftReminderToasts');
            if (!wrap) return;
            const label = tier === 60 ? '1 hour' : (tier === 90 ? '1.5 hours' : '2 hours');
            const el = document.createElement('div');
            el.className = 'rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-gray-800 shadow-lg';
            let html = '<div class="flex items-start justify-between gap-2"><div>';
            html += '<div class="font-semibold text-amber-800">Shift in ~' + label + '</div>';
            html += '<div class="mt-0.5 font-medium">' + (r.guard_name || 'Guard') + '</div>';
            if (r.label)    html += '<div class="text-xs text-gray-600">' + r.label + '</div>';
            if (r.location) html += '<div class="text-xs text-gray-600">' + r.location + '</div>';
            html += '<div class="mt-0.5 text-xs text-gray-600">Starts ' + r.start_time + ' &middot; ' + r.date + '</div>';
            html += '</div><button type="button" class="text-lg leading-none text-gray-400 hover:text-gray-700">&times;</button></div>';
            el.innerHTML = html;
            el.querySelector('button').addEventListener('click', function () { el.remove(); });
            wrap.appendChild(el);
            setTimeout(function () { el.remove(); }, 60000);
        }

        async function poll() {
            let data;
            try {
                const res = await fetch(ENDPOINT, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                data = await res.json();
            } catch (e) { return; }

            const shown = loadShown();
            const now = Date.now();

            (data.reminders || []).forEach(function (r) {
                const start = new Date(r.starts_at).getTime();
                if (isNaN(start)) return;
                const mins = (start - now) / 60000;
                if (mins < 0) return;

                for (let i = 0; i < TIERS.length; i++) {
                    const T = TIERS[i];
                    if (mins <= T && mins > (T - CATCH)) {
                        const id = r.key + '|' + T;
                        if (!shown.has(id)) { showToast(r, T); shown.add(id); }
                        break; // most urgent applicable tier only
                    }
                }
            });

            saveShown(shown);
        }

        document.addEventListener('DOMContentLoaded', function () {
            poll();
            setInterval(poll, POLL_MS);
        });
    })();
    </script>

    {{-- V3-P5: staff pay-date reminders. Same architecture as the shift
         poller above (client-side, no cron), with its own endpoint and its
         own localStorage key so the two never collide. Fires the day
         before; the server stops feeding an event once every guard on it
         is marked paid. --}}
    <script>
    (function () {
        const ENDPOINT  = "{{ route('chaseup.payDateReminders') }}";
        const POLL_MS   = 300000;   // 5 min — this is a date-level signal, not minute-level
        const STORE_KEY = 'gms_paydate_reminders_shown';

        function loadShown() {
            try { return new Set(JSON.parse(localStorage.getItem(STORE_KEY) || '[]')); }
            catch (e) { return new Set(); }
        }
        function saveShown(set) {
            try { localStorage.setItem(STORE_KEY, JSON.stringify([].concat([...set]).slice(-200))); } catch (e) {}
        }

        function showToast(r) {
            const wrap = document.getElementById('shiftReminderToasts');
            if (!wrap) return;
            const el = document.createElement('div');
            el.className = 'rounded-lg border border-blue-300 bg-blue-50 p-3 text-sm text-gray-800 shadow-lg';
            let html = '<div class="flex items-start justify-between gap-2"><div>';
            html += '<div class="font-semibold text-blue-800">Staff pay due tomorrow</div>';
            html += '<div class="mt-0.5 font-medium">' + (r.event_name || 'Event') + '</div>';
            html += '<div class="mt-0.5 text-xs text-gray-600">' + r.guards_due + ' of ' + r.guards_total + ' guards still unpaid</div>';
            html += '<div class="text-xs text-gray-600">Pay date ' + r.pay_date + '</div>';
            if (r.url) html += '<a href="' + r.url + '" class="mt-1 inline-block text-xs font-semibold text-blue-700 underline">Open pay sheet</a>';
            html += '</div><button type="button" class="text-lg leading-none text-gray-400 hover:text-gray-700">&times;</button></div>';
            el.innerHTML = html;
            el.querySelector('button').addEventListener('click', function () { el.remove(); });
            wrap.appendChild(el);
            setTimeout(function () { el.remove(); }, 120000);
        }

        async function pollPayDates() {
            let data;
            try {
                const res = await fetch(ENDPOINT, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) return;
                data = await res.json();
            } catch (e) { return; }

            const shown = loadShown();
            (data.reminders || []).forEach(function (r) {
                if (!r.key || shown.has(r.key)) return;
                showToast(r);
                shown.add(r.key);
            });
            saveShown(shown);
        }

        document.addEventListener('DOMContentLoaded', function () {
            pollPayDates();
            setInterval(pollPayDates, POLL_MS);
        });
    })();
    </script>
    @endauth

</body>
</html>
