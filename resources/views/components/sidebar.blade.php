<!-- Sidebar - Blue gradient, no logo (logo is in navbar now) -->
<aside
    class="sidebar fixed left-0 top-0 h-full bg-gradient-to-b from-[#29AAE1] to-[#1E8CC0] z-50 shadow-xl transition-all duration-300 flex flex-col"
    :class="sidebarOpen ? 'w-[180px]' : 'w-[60px]'">

    <!-- Spacer for navbar height -->
    <div class="h-14"></div>

    <!-- Navigation -->
    <nav class="flex-1 py-3 px-2 space-y-1">
        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15 {{ request()->routeIs('dashboard') ? 'bg-white/20' : '' }}"
            :class="!sidebarOpen && 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">Dashboard</span>
        </a>

        <a href="{{ route('map') }}"
            class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15 {{ request()->routeIs('map') ? 'bg-white/20' : '' }}"
            :class="!sidebarOpen && 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">Map</span>
        </a>

        <a href="#"
            class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15"
            :class="!sidebarOpen && 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">PJU Report</span>
        </a>

        <a href="#"
            class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15"
            :class="!sidebarOpen && 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">Analytics</span>
        </a>

        @if(auth()->user() && auth()->user()->isAdmin())
            <a href="{{ route('users.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15 {{ request()->routeIs('users.*') ? 'bg-white/20' : '' }}"
                :class="!sidebarOpen && 'justify-center'">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">User Management</span>
            </a>
        @endif
    </nav>

    <!-- Bottom -->
    <div class="border-t border-white/20 p-2 space-y-1">
        <button @click="toggleSidebar()"
            class="flex items-center gap-3 px-3 py-2 text-white rounded-lg transition-all duration-200 hover:bg-white/15 w-full"
            :class="!sidebarOpen && 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0 transition-transform" :class="!sidebarOpen && 'rotate-180'" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">Collapse</span>
        </button>

        <button @click="toggleDarkMode()"
            class="flex items-center gap-3 px-3 py-2 text-white rounded-lg transition-all duration-200 hover:bg-white/15 w-full"
            :class="!sidebarOpen && 'justify-center'">
            <svg x-show="!darkMode" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <svg x-show="darkMode" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">Dark Mode</span>
        </button>
    </div>
</aside>