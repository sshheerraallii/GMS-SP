<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIA...">
    <title>@yield('title', 'Security Guard')</title>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="min-h-screen flex flex-col">

        <!-- Navbar / Header -->
        <header class="bg-gray-800 text-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <a href="{{ route('events.index') }}">
                            <img class="h-12 w-12" src="{{ asset('secure premises.png') }}" alt="Secure Premises">
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <nav class="hidden md:flex space-x-4">
                        <a href="{{ route('security-guards.index') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md transition">Guards</a>
                        <a href="{{ route('clients.index') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md transition">Clients</a>
                        <a href="{{ route('events.index') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md transition">Events</a>
                        <a href="{{ route('login.register') }}" class="hover:bg-gray-700 px-3 py-2 rounded-md transition">Register Admin</a>
                    </nav>

                    <!-- User Info & Logout -->
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium">{{ Auth::user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="flex">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-gray-700 hover:bg-gray-600 px-3 py-2 rounded-md transition">Logout</button>
                        </form>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden">
                        <button id="mobile-menu-button" class="text-gray-200 hover:text-white focus:outline-none text-2xl">
                            ☰
                        </button>
                    </div>
                </div>

                <!-- Mobile Menu -->
                <div id="mobile-menu" class="md:hidden hidden mt-2 space-y-1">
                    <a href="{{ route('security-guards.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-700">Guards</a>
                    <a href="{{ route('clients.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-700">Clients</a>
                    <a href="{{ route('events.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-700">Events</a>
                    <a href="{{ route('login.register') }}" class="block px-3 py-2 rounded-md hover:bg-gray-700">Register Admin</a>
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
                &copy; {{ date('Y') }} Security Guard App. All rights reserved.
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
