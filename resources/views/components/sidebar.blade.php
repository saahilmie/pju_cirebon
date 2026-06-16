<!-- Sidebar - Blue gradient, starts below navbar (T-Shape Layout) -->
<aside
    class="sidebar fixed left-0 top-14 bg-gradient-to-b from-[#29AAE1] to-[#1E8CC0] z-40 shadow-xl transition-all duration-300 flex flex-col"
    :class="sidebarOpen ? 'w-[180px]' : 'w-[60px]'" style="height: calc(100vh - 56px);">

    <!-- Navigation -->
    <nav class="flex-1 py-4 px-2 space-y-1 overflow-y-auto">
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

        <a href="{{ route('pju-report') }}"
            class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15 {{ request()->routeIs('pju-report') ? 'bg-white/20' : '' }}"
            :class="!sidebarOpen && 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">PJU Report</span>
        </a>

        <a href="{{ route('analytics') }}"
            class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15 {{ request()->routeIs('analytics') ? 'bg-white/20' : '' }}"
            :class="!sidebarOpen && 'justify-center'">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span x-show="sidebarOpen" class="whitespace-nowrap text-sm">Analytics</span>
        </a>

        @if(auth()->user() && auth()->user()->isAdmin())
            <!-- Update Data Dropdown -->
            <div x-data="{ updateDataOpen: {{ request()->routeIs('photo-upload') || request()->routeIs('bulk-status-update') ? 'true' : 'false' }} }">
                <button @click="updateDataOpen = !updateDataOpen"
                    class="flex items-center gap-3 px-3 py-2.5 text-white rounded-lg transition-all duration-200 hover:bg-white/15 w-full {{ request()->routeIs('photo-upload') || request()->routeIs('bulk-status-update') ? 'bg-white/20' : '' }}"
                    :class="!sidebarOpen && 'justify-center'">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-show="sidebarOpen" class="whitespace-nowrap text-sm flex-1 text-left">Update Data</span>
                    <svg x-show="sidebarOpen" class="w-4 h-4 flex-shrink-0 transition-transform duration-200" :class="updateDataOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Items -->
                <div x-show="updateDataOpen && sidebarOpen" x-collapse class="ml-4 mt-1 space-y-1">
                    <a href="{{ route('photo-upload') }}"
                        class="flex items-center gap-2 px-3 py-2 text-white/90 rounded-lg transition-all duration-200 hover:bg-white/15 text-sm {{ request()->routeIs('photo-upload') ? 'bg-white/15' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Photo Upload</span>
                    </a>
                    <a href="{{ route('bulk-status-update') }}"
                        class="flex items-center gap-2 px-3 py-2 text-white/90 rounded-lg transition-all duration-200 hover:bg-white/15 text-sm {{ request()->routeIs('bulk-status-update') ? 'bg-white/15' : '' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span>Status Update</span>
                    </a>
                </div>

                <!-- Collapsed Tooltip Sub-items (when sidebar collapsed) -->
                <div x-show="updateDataOpen && !sidebarOpen" x-collapse class="mt-1 space-y-1">
                    <a href="{{ route('photo-upload') }}"
                        class="flex items-center justify-center px-3 py-2 text-white/90 rounded-lg transition-all duration-200 hover:bg-white/15 {{ request()->routeIs('photo-upload') ? 'bg-white/15' : '' }}"
                        title="Photo Upload">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </a>
                    <a href="{{ route('bulk-status-update') }}"
                        class="flex items-center justify-center px-3 py-2 text-white/90 rounded-lg transition-all duration-200 hover:bg-white/15 {{ request()->routeIs('bulk-status-update') ? 'bg-white/15' : '' }}"
                        title="Status Update">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </a>
                </div>
            </div>
        @endif

        @if(auth()->user() && auth()->user()->isSuperAdmin())
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

    <!-- Bottom Actions -->
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
    </div>
</aside>