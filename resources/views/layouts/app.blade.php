<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'HotelMaint Pro')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Additional Styles -->
    <style>
        .sidebar { min-height: 100vh; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .status-open { background-color: #fee2e2; color: #dc2626; }
        .status-in-progress { background-color: #fef3c7; color: #d97706; }
        .status-pending-parts { background-color: #dbeafe; color: #2563eb; }
        .status-resolved { background-color: #d1fae5; color: #059669; }
        .status-closed { background-color: #f3f4f6; color: #6b7280; }
        .priority-critical { border-left: 4px solid #dc2626; }
        .priority-high { border-left: 4px solid #ea580c; }
        .priority-medium { border-left: 4px solid #ca8a04; }
        .priority-low { border-left: 4px solid #65a30d; }
    </style>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-slate-800 text-white flex-shrink-0 hidden md:block">
            <div class="p-4">
                <h1 class="text-xl font-bold">🏨 HotelMaint Pro</h1>
                <p class="text-xs text-slate-400 mt-1">Maintenance Management</p>
            </div>
            
            <nav class="mt-6">
                @auth
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center px-4 py-3 {{ request()->routeIs('dashboard') ? 'bg-slate-700' : 'hover:bg-slate-700' }} transition-colors">
                        <span class="mr-3">📊</span>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="{{ route('work-orders.index') }}" 
                       class="flex items-center px-4 py-3 {{ request()->routeIs('work-orders.*') ? 'bg-slate-700' : 'hover:bg-slate-700' }} transition-colors">
                        <span class="mr-3">🔧</span>
                        <span>Work Orders</span>
                    </a>
                    
                    @if(in_array(auth()->user()->role->name, ['admin', 'front_desk', 'supervisor']))
                        <a href="{{ route('complaints.index') }}" 
                           class="flex items-center px-4 py-3 {{ request()->routeIs('complaints.*') ? 'bg-slate-700' : 'hover:bg-slate-700' }} transition-colors">
                            <span class="mr-3">⚠️</span>
                            <span>Complaints</span>
                        </a>
                    @endif
                    
                    @if(in_array(auth()->user()->role->name, ['admin', 'supervisor', 'technician']))
                        <a href="{{ route('assets.index') }}" 
                           class="flex items-center px-4 py-3 {{ request()->routeIs('assets.*') ? 'bg-slate-700' : 'hover:bg-slate-700' }} transition-colors">
                            <span class="mr-3">🏢</span>
                            <span>Assets</span>
                        </a>
                        
                        <a href="{{ route('schedules.index') }}" 
                           class="flex items-center px-4 py-3 {{ request()->routeIs('schedules.*') ? 'bg-slate-700' : 'hover:bg-slate-700' }} transition-colors">
                            <span class="mr-3">📅</span>
                            <span>Schedules</span>
                        </a>
                    @endif
                    
                    @if(in_array(auth()->user()->role->name, ['admin', 'supervisor', 'manager']))
                        <a href="{{ route('reports.index') }}" 
                           class="flex items-center px-4 py-3 {{ request()->routeIs('reports.*') ? 'bg-slate-700' : 'hover:bg-slate-700' }} transition-colors">
                            <span class="mr-3">📈</span>
                            <span>Reports</span>
                        </a>
                    @endif
                    
                    <div class="border-t border-slate-700 my-4"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-3 hover:bg-slate-700 transition-colors">
                            <span class="mr-3">🚪</span>
                            <span>Logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="flex items-center px-4 py-3 hover:bg-slate-700 transition-colors">
                        <span class="mr-3">🔐</span>
                        <span>Login</span>
                    </a>
                @endauth
            </nav>
            
            @auth
                <div class="absolute bottom-0 w-64 p-4 bg-slate-900">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400">{{ ucfirst(auth()->user()->role->name) }}</p>
                        </div>
                    </div>
                </div>
            @endauth
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Mobile Header -->
            <header class="bg-white shadow-sm md:hidden">
                <div class="flex items-center justify-between px-4 py-3">
                    <h1 class="text-lg font-bold text-slate-800">🏨 HotelMaint Pro</h1>
                    <button id="mobile-menu-btn" class="text-slate-600 hover:text-slate-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-slate-800 text-white">
                <nav class="p-4 space-y-2">
                    <a href="{{ route('dashboard') }}" class="block py-2 hover:bg-slate-700 rounded">Dashboard</a>
                    <a href="{{ route('work-orders.index') }}" class="block py-2 hover:bg-slate-700 rounded">Work Orders</a>
                    @auth
                        @if(in_array(auth()->user()->role->name, ['admin', 'front_desk', 'supervisor']))
                            <a href="{{ route('complaints.index') }}" class="block py-2 hover:bg-slate-700 rounded">Complaints</a>
                        @endif
                    @endauth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left py-2 hover:bg-slate-700 rounded">Logout</button>
                    </form>
                </nav>
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
    
    @stack('scripts')
</body>
</html>
