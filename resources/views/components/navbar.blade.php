<!-- Navbar - Full Width at Top (T-Shape Layout) -->
<header class="bg-white h-14 flex items-center justify-between px-4 shadow-md fixed top-0 left-0 right-0 z-50">
    <!-- Left: Logo + Page Title with Spacing -->
    <div class="flex items-center">
        <!-- Logo -->
        <img src="{{ asset('images/pln-sipju-logo.png') }}" alt="PLN SIPJU" class="h-9">

        <!-- Separator/Spacing -->
        <div class="w-px h-8 bg-gray-300 mx-4"></div>

        <!-- Page Title -->
        <h1 class="text-lg font-bold text-gray-800">@yield('page-title', 'Dashboard')</h1>
    </div>

    <!-- Right: User Profile -->
    @auth
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                class="flex items-center gap-2 hover:bg-gray-100 rounded-lg px-3 py-2 transition-colors">
                <div
                    class="w-9 h-9 bg-[#29AAE1]/20 rounded-full flex items-center justify-center text-[#29AAE1] overflow-hidden">
                    @php
                        $names = explode(' ', auth()->user()->name);
                        $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                    @endphp
                    @if(auth()->user()->avatar)
                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-sm font-bold">{{ $initials }}</span>
                    @endif
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 transition-transform hidden sm:block" :class="open && 'rotate-180'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" @click.away="open = false" x-transition
                class="absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl border py-2 z-50">
                <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Profile Settings
                </a>
                <button @click="openLogoutModal(); open = false"
                    class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-gray-100 w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Log Out
                </button>
            </div>
        </div>
    @endauth
</header>