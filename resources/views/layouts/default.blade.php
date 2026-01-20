<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none !important}</style>

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

                <a href="{{ route('clients.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('clients.*') ? $active : $idle }}">
                    Clients
                </a>

                <a href="{{ route('events.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('events.*') ? $active : $idle }}">
                    Events
                </a>
@can('viewReports')
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

                <a href="{{ route('clients.index') }}"
                   class="{{ $mBase }} {{ request()->routeIs('clients.*') ? $mActive : $mIdle }}">
                    Clients
                </a>

                <a href="{{ route('events.index') }}"
                   class="{{ $mBase }} {{ request()->routeIs('events.*') ? $mActive : $mIdle }}">
                    Events
                </a>
@can('viewReports')
    <a href="{{ route('reports.home') }}"
       class="{{ $mBase }} {{ request()->routeIs('reports.*') ? $mActive : $mIdle }}">
        Reports
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
                &copy; {{ date('Y') }} Developed By WREDD.
            </div>
        </footer>
    </div>

    <!-- Mobile Menu Script -->
    <script>
        const button = document.getElementById('mobile-menu-button');
        const menu = document.getElementById('mobile-menu');

        button.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>

</body>
</html>
