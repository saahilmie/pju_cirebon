<!-- Main Layout - White navbar, logo in navbar, font 14px -->
<!DOCTYPE html>
<html lang="id" x-data="appState()" :class="{ 'dark': darkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PLN SIPJU') - PLN SIPJU Cirebon</title>

    <link rel="icon" type="image/png" href="{{ asset('images/pln-sipju-logo.png') }}">

    <!-- Critical CSS - must load before Alpine.js -->
    <style>
        html {
            font-size: 14px;
        }

        /* Prevent flash of unstyled content for Alpine.js elements */
        [x-cloak] {
            display: none !important;
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col transition-all duration-300" :class="sidebarOpen ? 'ml-[180px]' : 'ml-[60px]'">
            <!-- Navbar -->
            @include('components.navbar')

            <!-- Page Content -->
            <main class="flex-1 @yield('main-class', 'p-6') mt-14 overflow-auto">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Logout Modal -->
    <div x-show="showLogoutModal" x-cloak x-transition.opacity
        class="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4"
        @click.self="showLogoutModal = false">
        <div x-show="showLogoutModal" x-transition
            class="bg-white rounded-xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#EB2027]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Log Out Confirmation</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to log out?</p>
            <div class="flex gap-3">
                <button @click="showLogoutModal = false"
                    class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium">Cancel</button>
                <form action="{{ route('logout') }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit"
                        class="w-full px-4 py-2.5 bg-[#EB2027] text-white rounded-lg hover:bg-red-700 font-medium">Yes,
                        Log Out</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

    <script>
        function appState() {
            return {
                sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
                darkMode: localStorage.getItem('darkMode') === 'true',
                showLogoutModal: false,
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                    localStorage.setItem('sidebarOpen', this.sidebarOpen);
                },
                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                },
                openLogoutModal() {
                    this.showLogoutModal = true;
                }
            };
        }
    </script>

    @stack('scripts')
</body>

</html>