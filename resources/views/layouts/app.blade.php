<!-- Main Layout - T-Shape: Navbar full width, Sidebar below navbar -->
<!DOCTYPE html>
<html lang="id" x-data="appState()">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PLN SIPJU') - PLN SIPJU Cirebon</title>

    <link rel="icon" type="image/png" href="{{ asset('images/pln-sipju-logo.png') }}">

    <!-- Critical CSS -->
    <style>
        html {
            font-size: 14px;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="min-h-screen bg-gray-100">
    <!-- T-Shape Layout: Navbar on top (full width), then Sidebar + Content below -->

    <!-- Navbar - Full Width at Top -->
    @include('components.navbar')

    <!-- Container for Sidebar + Content (below navbar) -->
    <div class="flex pt-14 min-h-screen">
        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Main Content -->
        <main class="flex-1 @yield('main-class', 'p-6') overflow-auto transition-all duration-300"
            :class="sidebarOpen ? 'ml-[180px]' : 'ml-[60px]'">
            @yield('content')
        </main>
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

    <!-- Real-Time Update Toast Notification -->
    <div x-data="realtimeUpdates()" x-init="initEcho()" x-cloak>
        <template x-for="(toast, index) in toasts" :key="index">
            <div x-show="toast.show" x-transition
                class="fixed bottom-4 right-4 bg-white border-l-4 shadow-lg rounded-lg p-4 max-w-sm z-[200]"
                :class="toast.action === 'deleted' ? 'border-red-500' : 'border-[#29AAE1]'"
                :style="'bottom:' + (20 + index * 80) + 'px'">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5" :class="toast.action === 'deleted' ? 'text-red-500' : 'text-[#29AAE1]'"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800" x-text="toast.message"></p>
                        <p class="text-xs text-gray-500 mt-1">Click to refresh data</p>
                    </div>
                    <button @click="dismissToast(index)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <button @click="refreshPage()" class="mt-2 text-xs text-[#29AAE1] hover:underline">
                    Refresh Now
                </button>
            </div>
        </template>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

    <script>
        function appState() {
            return {
                sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
                showLogoutModal: false,
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                    localStorage.setItem('sidebarOpen', this.sidebarOpen);
                },
                openLogoutModal() {
                    this.showLogoutModal = true;
                }
            };
        }

        // Real-time updates handler
        function realtimeUpdates() {
            return {
                toasts: [],
                initEcho() {
                    if (typeof window.Echo !== 'undefined') {
                        window.Echo.channel('pju-updates')
                            .listen('.pju.updated', (e) => {
                                // Show notification to everyone for testing/visibility
                                this.showToast(e);
                            });
                    }
                },
                showToast(event) {
                    this.toasts.push({
                        show: true,
                        message: event.message,
                        action: event.action,
                        idpel: event.idpel
                    });
                    // Auto dismiss after 8 seconds
                    setTimeout(() => {
                        if (this.toasts.length > 0) {
                            this.toasts[0].show = false;
                            setTimeout(() => this.toasts.shift(), 300);
                        }
                    }, 8000);
                },
                dismissToast(index) {
                    this.toasts[index].show = false;
                    setTimeout(() => this.toasts.splice(index, 1), 300);
                },
                refreshPage() {
                    window.location.reload();
                }
            };
        }
    </script>

    @stack('scripts')
</body>

</html>