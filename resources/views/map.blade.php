@extends('layouts.app')

@section('title', 'Map')
@section('page-title', 'Map')
@section('main-class', 'p-0')

@section('content')
    <div x-data="mapPage()" x-init="init()" class="relative h-[calc(100vh-56px)] flex">
        <!-- Map Container -->
        <div class="flex-1 relative">
            <!-- Search & Filters (Centered) -->
            <div class="absolute top-4 left-1/2 -translate-x-1/2 z-[1000] flex items-center gap-4">
                <div class="bg-white rounded-lg shadow-lg px-4 py-2.5 flex items-center gap-2 w-64">
                    <input type="text" placeholder="Search by IDPEL" x-model="searchQuery" @keyup.enter="searchIdpel()"
                        class="flex-1 outline-none text-sm text-gray-700 placeholder-gray-400">
                    <button @click="searchIdpel()" class="text-[#29AAE1] hover:text-[#1E8CC0]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="bg-white rounded-lg shadow-lg px-5 py-2.5 flex items-center gap-2 text-sm text-gray-700 hover:bg-gray-50 min-w-[140px]">
                        <span x-text="selectedRegion || 'Regional'"></span>
                        <svg class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute top-full mt-2 bg-white rounded-lg shadow-xl py-2 w-52 z-50">
                        <button @click="filterByRegion(null); open = false"
                            class="w-full flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer text-left text-sm">
                            Semua Regional
                        </button>
                        <template x-for="region in regions" :key="region.name">
                            <button @click="filterByRegion(region.name); open = false"
                                class="w-full flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer text-left">
                                <span class="w-3 h-3 rounded-full" :style="'background-color:' + region.color"></span>
                                <span x-text="region.label" class="text-sm"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="bg-white rounded-lg shadow-lg px-5 py-2.5 flex items-center gap-2 text-sm text-gray-700 hover:bg-gray-50 min-w-[120px]">
                        <span x-text="selectedStatus || 'Status'"></span>
                        <svg class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        class="absolute top-full mt-2 bg-white rounded-lg shadow-xl py-2 w-44 z-50">
                        <button @click="filterByStatus(null); open = false"
                            class="w-full flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer text-left text-sm">
                            Semua Status
                        </button>
                        <button @click="filterByStatus('M'); open = false"
                            class="w-full flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer text-left">
                            <span class="w-3 h-3 rounded-full bg-[#17C353]"></span> <span class="text-sm">Meterisasi</span>
                        </button>
                        <button @click="filterByStatus('A'); open = false"
                            class="w-full flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer text-left">
                            <span class="w-3 h-3 rounded-full bg-[#FBED21]"></span> <span class="text-sm">Abonemen</span>
                        </button>
                        <button @click="filterByStatus('unclear'); open = false"
                            class="w-full flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer text-left">
                            <span class="w-3 h-3 rounded-full bg-[#EB2027]"></span> <span class="text-sm">Unclear</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Map -->
            <div id="main-map" class="w-full h-full"></div>

            <!-- Legend (Bottom Left) -->
            <div class="absolute bottom-6 left-4 bg-white rounded-xl shadow-xl p-4 z-[1000] min-w-[160px]">
                <h4 class="font-bold text-gray-800 mb-3">Legend</h4>
                <div class="space-y-2.5 text-sm">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-700" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
                        </svg>
                        <span class="text-gray-600">Unclear</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 border-2 border-gray-700 rounded-full"></div>
                        <span class="text-gray-600">IDPEL Main</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-4 h-4 bg-gray-700 rounded-full"></div>
                        <span class="text-gray-600">Meterisasi</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-0 h-0 border-l-[8px] border-r-[8px] border-b-[14px] border-l-transparent border-r-transparent border-b-gray-700">
                        </div>
                        <span class="text-gray-600">Abonemen</span>
                    </div>
                </div>
                <div class="border-t mt-4 pt-3 space-y-2.5 text-sm">
                    <p class="text-gray-500 font-semibold">Status</p>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-[#17C353]"></div>
                        <span class="text-gray-600">Meterisasi</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-[#FBED21]"></div>
                        <span class="text-gray-600">Abonemen</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-[#EB2027]"></div>
                        <span class="text-gray-600">Unclear</span>
                    </div>
                </div>
            </div>

            <!-- Popup on marker click -->
            <div x-show="hoveredPoint" x-transition class="fixed bg-white rounded-xl shadow-2xl p-4 z-[1002] min-w-[200px]"
                :style="'left:' + popupX + 'px; top:' + popupY + 'px'">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-5 h-5 border-2 rounded-full"
                        :class="hoveredPoint?.kdam === 'M' ? 'border-[#17C353]' : hoveredPoint?.kdam === 'A' ? 'border-[#FBED21]' : 'border-[#EB2027]'">
                    </div>
                    <span class="font-bold text-gray-800">ID Pel - <span x-text="hoveredPoint?.idpel"></span></span>
                </div>
                <p class="text-sm text-gray-600 mb-2" x-text="hoveredPoint?.nama_kabupaten"></p>
                <div class="flex justify-between text-sm">
                    <div>
                        <p class="text-gray-500">Jumlah</p>
                        <p class="font-bold text-gray-800" x-text="hoveredPoint?.jumlah_lampu || 1"></p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500">Status</p>
                        <p class="font-bold"
                            :class="hoveredPoint?.kdam === 'M' ? 'text-[#17C353]' : hoveredPoint?.kdam === 'A' ? 'text-[#FBED21]' : 'text-[#EB2027]'"
                            x-text="hoveredPoint?.kdam === 'M' ? 'Meterisasi' : hoveredPoint?.kdam === 'A' ? 'Abonemen' : 'Unclear'">
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Panel (Right Side) -->
        <div x-show="selectedPoint" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            class="w-[320px] bg-white shadow-2xl z-[1001] flex flex-col overflow-hidden border-l">

            <!-- Header: View IDPEL -->
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <span class="font-semibold text-gray-800">View <span x-text="selectedPoint?.idpel"></span></span>
                <button @click="closeDetail()" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
            </div>

            <!-- Photo Section -->
            <div class="relative h-44 bg-gray-100 flex items-center justify-center">
                <span class="text-gray-400 text-sm">Photo akan ditampilkan di sini</span>
            </div>

            <!-- Info Content -->
            <div class="flex-1 overflow-y-auto p-4 text-sm relative">
                <!-- PLN Logo Watermark in data section - centered, bigger, 25% opacity -->
                <img src="{{ asset('images/pln-sipju-logo.png') }}"
                    class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-48 opacity-25 pointer-events-none">

                <table class="w-full">
                    <tr class="h-8">
                        <td class="text-gray-600 w-28">No ID Pel</td>
                        <td class="w-4">:</td>
                        <td class="text-gray-800" x-text="selectedPoint?.idpel || '-'"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Nama</td>
                        <td>:</td>
                        <td class="text-gray-800" x-text="selectedPoint?.nama || '-'"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Tarif / Daya</td>
                        <td>:</td>
                        <td class="text-[#29AAE1]"
                            x-text="(selectedPoint?.tarif || '-') + ' / ' + (selectedPoint?.daya || '-') + ' VA'"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Wilayah Dishub</td>
                        <td>:</td>
                        <td class="text-[#29AAE1]"
                            x-text="selectedPoint?.nama_kabupaten?.replace('KAB. ', '').replace('KOTA ', '') || '-'"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Alamat</td>
                        <td>:</td>
                        <td class="text-[#29AAE1]" x-text="selectedPoint?.alamat || '-'"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Status Meter</td>
                        <td>:</td>
                        <td :class="selectedPoint?.kdam === 'M' ? 'text-[#17C353]' : selectedPoint?.kdam === 'A' ? 'text-[#FBED21]' : 'text-[#EB2027]'"
                            x-text="selectedPoint?.kdam === 'M' ? 'Meterisasi' : selectedPoint?.kdam === 'A' ? 'Abonemen' : 'Unclear'">
                        </td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">No Meter</td>
                        <td>:</td>
                        <td class="text-[#29AAE1]"
                            x-text="selectedPoint?.no_meter ? 'PRABAYAR (' + selectedPoint.no_meter + ')' : '-'"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Jumlah Lampu</td>
                        <td>:</td>
                        <td class="text-[#EB2027] font-bold" x-text="selectedPoint?.jumlah_lampu || 1"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Data Gardu</td>
                        <td>:</td>
                        <td class="text-[#29AAE1]" x-text="selectedPoint?.gardu || '-'"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Titik Koordinat</td>
                        <td>:</td>
                        <td class="font-mono text-xs text-gray-700"
                            x-text="(selectedPoint?.koordinat_x || '-') + ', ' + (selectedPoint?.koordinat_y || '-')"></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function mapPage() {
                return {
                    map: null,
                    markers: [],
                    markerLayer: null,
                    selectedPoint: null,
                    hoveredPoint: null,
                    popupX: 0,
                    popupY: 0,
                    searchQuery: '',
                    selectedRegion: null,
                    selectedStatus: null,
                    allMarkersData: [],
                    regions: [
                        { name: 'KAB. CIREBON', label: 'Kab. Cirebon', color: '#B51CEC' },
                        { name: 'KOTA CIREBON', label: 'Kota Cirebon', color: '#29AAE1' },
                        { name: 'KAB. INDRAMAYU', label: 'Indramayu', color: '#EB2027' },
                        { name: 'MAJALENGKA', label: 'Majalengka', color: '#FBED21' },
                        { name: 'KAB. KUNINGAN', label: 'Kuningan', color: '#17C353' }
                    ],

                    init() {
                        // Prevent double initialization
                        const container = L.DomUtil.get('main-map');
                        if (container != null) {
                            container._leaflet_id = null;
                        }
                        
                        this.map = L.map('main-map', { zoomControl: true }).setView([-6.7320, 108.5523], 11);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: ' OpenStreetMap' }).addTo(this.map);
                        this.markerLayer = L.layerGroup().addTo(this.map);
                        this.addRegionalOverlays();
                        this.loadMarkers();
                    },

                    addRegionalOverlays() {
                        const regionData = [
                            { name: 'KOTA CIREBON', color: '#29AAE1', coords: [[-6.69, 108.52], [-6.69, 108.59], [-6.76, 108.59], [-6.76, 108.52]] },
                            { name: 'KAB. CIREBON', color: '#B51CEC', coords: [[-6.60, 108.35], [-6.60, 108.65], [-6.85, 108.65], [-6.85, 108.35]] },
                            { name: 'KAB. INDRAMAYU', color: '#EB2027', coords: [[-6.25, 108.00], [-6.25, 108.40], [-6.55, 108.40], [-6.55, 108.00]] },
                            { name: 'MAJALENGKA', color: '#FBED21', coords: [[-6.70, 108.10], [-6.70, 108.35], [-6.95, 108.35], [-6.95, 108.10]] },
                            { name: 'KAB. KUNINGAN', color: '#17C353', coords: [[-6.85, 108.40], [-6.85, 108.65], [-7.05, 108.65], [-7.05, 108.40]] }
                        ];
                        regionData.forEach(r => {
                            L.polygon(r.coords, { color: r.color, weight: 2, fillColor: r.color, fillOpacity: 0.15 })
                                .bindTooltip(r.name, { permanent: false }).addTo(this.map);
                        });
                    },

                    async loadMarkers() {
                        try {
                            const response = await fetch('/api/pju-markers?limit=2000');
                            const data = await response.json();

                            // Group markers by IDPEL for connecting lines
                            const idpelGroups = {};
                            console.log('Loaded markers:', data.length);
                            data.forEach(p => {
                                // Parse coordinates as floats (they come as strings from DB)
                                const lat = parseFloat(p.koordinat_x);
                                const lng = parseFloat(p.koordinat_y);
                                
                                if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                                    p.koordinat_x = lat;
                                    p.koordinat_y = lng;
                                    this.addMarker(p);
                                    // Group by IDPEL for connecting lines
                                    if (p.idpel) {
                                        if (!idpelGroups[p.idpel]) idpelGroups[p.idpel] = [];
                                        idpelGroups[p.idpel].push([lat, lng]);
                                    }
                                }
                            });

                            // Draw connecting lines for same IDPEL (multiple lamps)
                            this.drawConnectingLines(idpelGroups);
                        } catch (e) {
                            console.error('Error loading markers:', e);
                        }
                    },

                    drawConnectingLines(idpelGroups) {
                        Object.entries(idpelGroups).forEach(([idpel, coords]) => {
                            if (coords.length > 1) {
                                // Draw polyline connecting all points with same IDPEL
                                const lineColor = '#29AAE1'; // Blue color for connections
                                L.polyline(coords, {
                                    color: lineColor,
                                    weight: 2,
                                    opacity: 0.7,
                                    dashArray: '5, 5'
                                }).addTo(this.markerLayer);
                            }
                        });
                    },

                    loadSampleMarkers() {
                        // Sample data - will be replaced by real database data
                    },

                    addMarker(point) {
                        const color = point.kdam === 'M' ? '#17C353' : point.kdam === 'A' ? '#FBED21' : '#EB2027';
                        let html;
                        if (!point.kdam || (point.kdam !== 'M' && point.kdam !== 'A')) {
                            html = `<svg width="16" height="16" viewBox="0 0 24 24" fill="${color}"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>`;
                        } else if (point.kdam === 'A') {
                            html = `<div style="width:0;height:0;border-left:8px solid transparent;border-right:8px solid transparent;border-bottom:14px solid ${color};"></div>`;
                        } else {
                            html = `<div style="width:14px;height:14px;background:${color};border-radius:50%;border:2px solid white;"></div>`;
                        }
                        const icon = L.divIcon({ html, className: 'custom-marker', iconSize: [16, 16], iconAnchor: [8, 8] });
                        const marker = L.marker([point.koordinat_x, point.koordinat_y], { icon }).addTo(this.markerLayer);

                        marker.on('click', (e) => {
                            this.selectedPoint = point;
                            this.hoveredPoint = point;
                            this.popupX = e.containerPoint.x + 20;
                            this.popupY = e.containerPoint.y - 50;
                            this.map.panTo([point.koordinat_x, point.koordinat_y]);
                        });

                        // Popup only appears on click, not hover

                        this.markers.push({ marker, data: point });
                    },

                    closeDetail() {
                        this.selectedPoint = null;
                        this.hoveredPoint = null;
                    },

                    searchIdpel() {
                        if (!this.searchQuery) return;
                        const found = this.markers.find(m => m.data.idpel?.includes(this.searchQuery));
                        if (found) {
                            this.selectedPoint = found.data;
                            this.map.setView([found.data.koordinat_x, found.data.koordinat_y], 16);
                        }
                    },

                    filterByRegion(regionName) {
                        this.selectedRegion = regionName ? this.regions.find(r => r.name === regionName)?.label : null;
                        this.applyFilters();
                    },

                    filterByStatus(status) {
                        if (status === null) {
                            this.selectedStatus = null;
                        } else if (status === 'M') {
                            this.selectedStatus = 'Meterisasi';
                        } else if (status === 'A') {
                            this.selectedStatus = 'Abonemen';
                        } else {
                            this.selectedStatus = 'Unclear';
                        }
                        this.applyFilters();
                    },

                    applyFilters() {
                        this.markers.forEach(({ marker, data }) => {
                            let showMarker = true;

                            // Filter by region
                            if (this.selectedRegion) {
                                const regionMatch = this.regions.find(r => r.label === this.selectedRegion);
                                if (regionMatch && data.nama_kabupaten !== regionMatch.name) {
                                    showMarker = false;
                                }
                            }

                            // Filter by status
                            if (this.selectedStatus) {
                                const statusMap = { 'Meterisasi': 'M', 'Abonemen': 'A', 'Unclear': null };
                                const expectedKdam = statusMap[this.selectedStatus];
                                if (this.selectedStatus === 'Unclear') {
                                    if (data.kdam === 'M' || data.kdam === 'A') showMarker = false;
                                } else if (data.kdam !== expectedKdam) {
                                    showMarker = false;
                                }
                            }

                            if (showMarker) {
                                marker.addTo(this.markerLayer);
                            } else {
                                this.markerLayer.removeLayer(marker);
                            }
                        });
                    }
                };
            }
        </script>
        <style>
            .custom-marker {
                background: transparent !important;
                border: none !important;
            }
        </style>
    @endpush
@endsection