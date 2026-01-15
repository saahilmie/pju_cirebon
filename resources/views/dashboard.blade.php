@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div x-data="dashboardPage()" x-init="init()" class="space-y-4">
        <!-- Toast Notification -->
        <div x-show="showToast" x-transition class="fixed top-4 right-4 z-[60]">
            <div class="bg-[#17C353] text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[280px]">
                <div class="w-7 h-7 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-[#17C353]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="font-medium text-sm" x-text="toastMessage"></p>
            </div>
            <div class="h-1 bg-white/30 rounded-b-lg overflow-hidden -mt-1 mx-1">
                <div class="h-full bg-white" :style="{ width: toastProgress + '%' }"></div>
            </div>
        </div>

        <!-- Overview Cards -->
        <section class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="bg-white rounded-lg shadow p-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-[#29AAE1]/10 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#29AAE1]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase">Location Points</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($stats['total_points']) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-[#17C353]/10 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#17C353]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase">Meterisasi</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($stats['total_meterisasi']) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-[#FBED21]/10 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#FBED21]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase">Abonemen</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($stats['total_abonemen']) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-[#EB2027]/10 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#EB2027]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase">Unclear</p>
                    <p class="text-lg font-bold text-gray-800">{{ number_format($stats['total_unclear']) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase">Users</p>
                    <p class="text-lg font-bold text-gray-800">{{ $stats['total_users'] }}</p>
                </div>
            </div>
        </section>

        <!-- Charts Row -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Stats Progress -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">Stats Progress</h3>
                    <div class="flex items-center gap-3 text-[10px]">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#17C353]"></span>
                            M</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#FBED21]"></span>
                            A</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#EB2027]"></span>
                            Unclear</span>
                    </div>
                </div>
                <div class="flex gap-2 h-40">
                    <div class="flex flex-col justify-between text-[10px] text-gray-500 pr-1">
                        <span>Max</span><span></span><span></span><span>0</span>
                    </div>
                    <div class="flex-1 bg-gray-50 rounded flex items-end justify-around px-2">
                        @foreach($regionalStats as $region => $data)
                            <div class="flex flex-col items-center gap-1 group" title="{{ $region }}">
                                <div class="flex gap-0.5 items-end">
                                    <div class="w-3 bg-[#17C353] rounded-t transition-all hover:opacity-80"
                                        style="height: {{ min(($data['M'] / max(1, max(array_column($regionalStats, 'M')))) * 120, 120) }}px;"
                                        title="M: {{ number_format($data['M']) }}"></div>
                                    <div class="w-3 bg-[#FBED21] rounded-t transition-all hover:opacity-80"
                                        style="height: {{ min(($data['A'] / max(1, max(array_column($regionalStats, 'A')))) * 120, 120) }}px;"
                                        title="A: {{ number_format($data['A']) }}"></div>
                                    <div class="w-3 bg-[#EB2027] rounded-t transition-all hover:opacity-80"
                                        style="height: {{ min(($data['unclear'] / max(1, max(array_column($regionalStats, 'unclear')))) * 120, 120) }}px;"
                                        title="Unclear: {{ number_format($data['unclear']) }}"></div>
                                </div>
                                <div class="flex flex-col items-center text-[8px] text-gray-600 leading-tight text-center">
                                    <span>{{ explode(' ', $region)[0] }}</span>
                                    <span class="font-medium">{{ implode(' ', array_slice(explode(' ', $region), 1)) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Maps Progress -->
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">Maps Progress</h3>
                    <div class="flex items-center gap-3 text-[10px]">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#17C353]"></span>
                            M</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#FBED21]"></span>
                            A</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-[#EB2027]"></span>
                            Unclear</span>
                    </div>
                </div>
                <div id="mini-map" class="h-40 rounded z-0"></div>
            </div>
        </section>

        <!-- Bottom Row -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Regional Details with Interactive Pie Chart -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Regional Details</h3>
                <div class="flex gap-4">
                    <div class="flex-1 space-y-1.5">
                        <p class="text-[10px] text-gray-500 uppercase mb-2">Regional Sections</p>
                        @foreach($regionalStats as $region => $data)
                            <div class="flex items-center gap-2 text-sm group cursor-pointer hover:bg-gray-50 py-0.5 px-1 rounded"
                                @mouseenter="highlightRegion('{{ $region }}')" @mouseleave="highlightRegion(null)">
                                <span class="w-2 h-2 rounded-full flex-shrink-0"
                                    style="background-color: {{ $data['color'] }};"></span>
                                <span class="text-gray-700 text-xs truncate flex-1">{{ $region }}</span>
                                <span class="text-xs font-semibold text-gray-800">{{ $data['percent'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="relative w-28 h-28 flex-shrink-0" x-data="{ hoveredRegion: null, hoveredData: null }">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            @php $offset = 0; @endphp
                            @foreach($regionalStats as $region => $data)
                                <circle cx="18" cy="18" r="14" fill="none" stroke="{{ $data['color'] }}" stroke-width="4"
                                    stroke-dasharray="{{ $data['percent'] }} {{ 100 - $data['percent'] }}"
                                    stroke-dashoffset="-{{ $offset }}"
                                    class="transition-all duration-200 cursor-pointer hover:opacity-80"
                                    @mouseenter="$dispatch('show-tooltip', { region: '{{ $region }}', count: {{ $data['total'] }}, percent: {{ $data['percent'] }} })"
                                    @mouseleave="$dispatch('hide-tooltip')">
                                    <title>{{ $region }}: {{ number_format($data['total']) }} ({{ $data['percent'] }}%)</title>
                                </circle>
                                @php $offset += $data['percent']; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-gray-800">{{ number_format($stats['total_points']) }}</span>
                            <span class="text-[9px] text-gray-500 uppercase">Total</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Details -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Users Details</h3>
                <div class="flex gap-4">
                    <div class="flex-1 space-y-1.5">
                        <p class="text-[10px] text-gray-500 uppercase mb-2">Users Roles</p>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-2 h-2 rounded-full bg-[#B51CEC]"></span>
                            <span class="text-gray-700 text-xs flex-1">Super Admin</span>
                            <span class="text-xs font-semibold text-gray-800">{{ $userRoles['super_admin'] }}%</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-2 h-2 rounded-full bg-[#FBED21]"></span>
                            <span class="text-gray-700 text-xs flex-1">Admin</span>
                            <span class="text-xs font-semibold text-gray-800">{{ $userRoles['admin'] }}%</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="w-2 h-2 rounded-full bg-[#29AAE1]"></span>
                            <span class="text-gray-700 text-xs flex-1">Employee</span>
                            <span class="text-xs font-semibold text-gray-800">{{ $userRoles['employee'] }}%</span>
                        </div>
                    </div>
                    <div class="relative w-28 h-28 flex-shrink-0">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#B51CEC" stroke-width="4"
                                stroke-dasharray="{{ $userRoles['super_admin'] }} {{ 100 - $userRoles['super_admin'] }}">
                                <title>Super Admin: {{ $userRoles['super_admin'] }}%</title>
                            </circle>
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#FBED21" stroke-width="4"
                                stroke-dasharray="{{ $userRoles['admin'] }} {{ 100 - $userRoles['admin'] }}"
                                stroke-dashoffset="-{{ $userRoles['super_admin'] }}">
                                <title>Admin: {{ $userRoles['admin'] }}%</title>
                            </circle>
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#29AAE1" stroke-width="4"
                                stroke-dasharray="{{ $userRoles['employee'] }} {{ 100 - $userRoles['employee'] }}"
                                stroke-dashoffset="-{{ $userRoles['super_admin'] + $userRoles['admin'] }}">
                                <title>Employee: {{ $userRoles['employee'] }}%</title>
                            </circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-lg font-bold text-gray-800">{{ $stats['total_users'] }}</span>
                            <span class="text-[9px] text-gray-500 uppercase">Users</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            function dashboardPage() {
                return {
                    showToast: false,
                    toastMessage: '',
                    toastProgress: 100,

                    init() {
                        @if(session('success'))
                            this.toastMessage = "{{ session('success') }}";
                            this.showToast = true;
                            this.toastProgress = 100;
                            const interval = setInterval(() => {
                                this.toastProgress -= 2;
                                if (this.toastProgress <= 0) { this.showToast = false; clearInterval(interval); }
                            }, 100);
                        @endif

                    if (document.getElementById('mini-map')) {
                            const miniMap = L.map('mini-map', {
                                zoomControl: true, dragging: true, scrollWheelZoom: true
                            }).setView([-6.7066, 108.5570], 9);

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);

                            const points = [
                                { lat: -6.7166, lng: 108.5570, status: 'M' },
                                { lat: -6.7066, lng: 108.5670, status: 'A' },
                                { lat: -6.6966, lng: 108.5470, status: null },
                            ];
                            points.forEach(p => {
                                let c = p.status === 'M' ? '#17C353' : p.status === 'A' ? '#FBED21' : '#EB2027';
                                L.circleMarker([p.lat, p.lng], { radius: 5, fillColor: c, color: c, weight: 1, fillOpacity: 0.8, interactive: false }).addTo(miniMap);
                            });
                        }
                    },

                    highlightRegion(region) {
                        // Future: highlight specific region on map
                    }
                };
            }
        </script>
    @endpush
@endsection