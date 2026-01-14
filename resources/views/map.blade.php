@extends('layouts.app')

@section('title', 'Map')
@section('page-title', 'Map')
@section('main-class', 'p-0')

@section('content')
<div x-data="mapPage()" x-init="init()" class="relative h-[calc(100vh-56px)] flex">
    <!-- Map Container - Full Page -->
    <div class="flex-1 relative">
        <!-- Search & Filters -->
        <div class="absolute top-4 left-1/2 -translate-x-1/2 z-[1000] flex items-center gap-3">
            <div class="bg-white rounded-lg shadow-lg px-4 py-2.5 flex items-center gap-2 w-72">
                <svg class="w-5 h-5 text-[#29AAE1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" placeholder="Search by IDPEL" x-model="searchQuery" @keyup.enter="searchIdpel()"
                       class="flex-1 outline-none text-sm text-gray-700 placeholder-gray-400">
            </div>
            
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="bg-white rounded-lg shadow-lg px-4 py-2.5 flex items-center gap-2 text-sm text-gray-700 hover:bg-gray-50">
                    Regional
                    <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute top-full mt-2 bg-white rounded-lg shadow-xl py-2 w-52 z-50">
                    <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" @change="filterByRegion('all')" class="rounded text-[#29AAE1]"> All Regions
                    </label>
                    <template x-for="region in regions" :key="region.name">
                        <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                            <span class="w-3 h-3 rounded-full" :style="'background-color:' + region.color"></span>
                            <span x-text="region.label" class="text-sm"></span>
                        </label>
                    </template>
                </div>
            </div>
            
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="bg-white rounded-lg shadow-lg px-4 py-2.5 flex items-center gap-2 text-sm text-gray-700 hover:bg-gray-50">
                    Status
                    <svg class="w-4 h-4" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute top-full mt-2 bg-white rounded-lg shadow-xl py-2 w-44 z-50">
                    <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" @change="filterByStatus('all')" class="rounded text-[#29AAE1]"> All Status
                    </label>
                    <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                        <span class="w-3 h-3 rounded-full bg-[#17C353]"></span> Meterisasi
                    </label>
                    <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                        <span class="w-3 h-3 rounded-full bg-[#FBED21]"></span> Abonemen
                    </label>
                    <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                        <span class="w-3 h-3 rounded-full bg-[#EB2027]"></span> Unclear
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Main Map -->
        <div id="main-map" class="w-full h-full"></div>
        
        <!-- Legend -->
        <div class="absolute bottom-6 left-4 bg-white rounded-xl shadow-xl p-4 z-[1000] min-w-[160px]">
            <h4 class="font-bold text-gray-800 mb-3">Legend</h4>
            <div class="space-y-2.5 text-sm">
                <div class="flex items-center gap-3">
                    <div class="w-4 h-4 bg-[#EB2027] rotate-45"></div>
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
                    <div class="w-0 h-0 border-l-[8px] border-r-[8px] border-b-[14px] border-l-transparent border-r-transparent border-b-gray-700"></div>
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
    </div>
    
    <!-- Detail Panel (Right Side) -->
    <div x-show="selectedPoint" x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
         class="w-[380px] bg-white shadow-2xl z-[1001] flex flex-col overflow-hidden border-l">
        
        <!-- Detail Header -->
        <div class="bg-gradient-to-r from-[#29AAE1] to-[#1E8CC0] p-4 text-white flex items-center justify-between">
            <div>
                <p class="text-xs opacity-80">IDPEL</p>
                <h3 class="text-xl font-bold" x-text="selectedPoint?.idpel || '-'"></h3>
            </div>
            <button @click="closeDetail()" class="p-2 hover:bg-white/20 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <!-- Detail Content -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            <!-- Status Badge -->
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-500">Status:</span>
                <span class="px-3 py-1 rounded-full text-xs font-bold"
                      :class="{
                          'bg-green-100 text-green-700': selectedPoint?.kdam === 'M',
                          'bg-yellow-100 text-yellow-700': selectedPoint?.kdam === 'A',
                          'bg-red-100 text-red-700': !selectedPoint?.kdam || (selectedPoint?.kdam !== 'M' && selectedPoint?.kdam !== 'A')
                      }"
                      x-text="selectedPoint?.kdam === 'M' ? 'Meterisasi' : selectedPoint?.kdam === 'A' ? 'Abonemen' : 'Unclear'">
                </span>
            </div>
            
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Nama</p>
                    <p class="font-medium text-gray-800 text-sm" x-text="selectedPoint?.nama || '-'"></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Kabupaten</p>
                    <p class="font-medium text-gray-800 text-sm" x-text="selectedPoint?.nama_kabupaten || '-'"></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Tarif</p>
                    <p class="font-medium text-gray-800 text-sm" x-text="selectedPoint?.tarif || '-'"></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Daya</p>
                    <p class="font-medium text-gray-800 text-sm" x-text="selectedPoint?.daya || '-'"></p>
                </div>
            </div>
            
            <!-- Address -->
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Alamat</p>
                <p class="font-medium text-gray-800 text-sm" x-text="selectedPoint?.alamat || '-'"></p>
            </div>
            
            <!-- Coordinates -->
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Koordinat</p>
                <p class="font-mono text-sm text-gray-700">
                    <span x-text="selectedPoint?.koordinat_x?.toFixed(6) || '-'"></span>,
                    <span x-text="selectedPoint?.koordinat_y?.toFixed(6) || '-'"></span>
                </p>
            </div>
            
            <!-- Gardu Info -->
            <div class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                <p class="text-xs text-blue-600 mb-1">Info Gardu</p>
                <p class="font-medium text-blue-800 text-sm" x-text="selectedPoint?.gardu || '-'"></p>
            </div>
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
        searchQuery: '',
        regions: [
            { name: 'KAB. CIREBON', label: 'Kab. Cirebon', color: '#B51CEC' },
            { name: 'KOTA CIREBON', label: 'Kota Cirebon', color: '#29AAE1' },
            { name: 'KAB. INDRAMAYU', label: 'Indramayu', color: '#EB2027' },
            { name: 'MAJALENGKA', label: 'Majalengka', color: '#FBED21' },
            { name: 'KAB. KUNINGAN', label: 'Kuningan', color: '#17C353' }
        ],
        regionPolygons: [],

        init() {
            this.map = L.map('main-map', {
                zoomControl: true,
                scrollWheelZoom: true
            }).setView([-6.7320, 108.5523], 11);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(this.map);

            this.markerLayer = L.layerGroup().addTo(this.map);
            
            // Add regional overlays
            this.addRegionalOverlays();
            
            // Load markers
            this.loadMarkers();
        },

        addRegionalOverlays() {
            // Regional boundary polygons (approximate)
            const regionData = [
                { 
                    name: 'KOTA CIREBON', color: '#29AAE1',
                    coords: [[-6.69, 108.52], [-6.69, 108.59], [-6.76, 108.59], [-6.76, 108.52]]
                },
                { 
                    name: 'KAB. CIREBON', color: '#B51CEC',
                    coords: [[-6.60, 108.35], [-6.60, 108.65], [-6.85, 108.65], [-6.85, 108.35]]
                },
                { 
                    name: 'KAB. INDRAMAYU', color: '#EB2027',
                    coords: [[-6.25, 108.00], [-6.25, 108.40], [-6.55, 108.40], [-6.55, 108.00]]
                },
                { 
                    name: 'MAJALENGKA', color: '#FBED21',
                    coords: [[-6.70, 108.10], [-6.70, 108.35], [-6.95, 108.35], [-6.95, 108.10]]
                },
                { 
                    name: 'KAB. KUNINGAN', color: '#17C353',
                    coords: [[-6.85, 108.40], [-6.85, 108.65], [-7.05, 108.65], [-7.05, 108.40]]
                }
            ];

            regionData.forEach(region => {
                const polygon = L.polygon(region.coords, {
                    color: region.color,
                    weight: 2,
                    fillColor: region.color,
                    fillOpacity: 0.15
                }).addTo(this.map);
                polygon.bindTooltip(region.name, { permanent: false, direction: 'center' });
                this.regionPolygons.push(polygon);
            });
        },

        async loadMarkers() {
            try {
                const response = await fetch('/api/pju-markers?limit=500');
                const data = await response.json();
                
                data.forEach(point => {
                    if (point.koordinat_x && point.koordinat_y) {
                        this.addMarker(point);
                    }
                });
            } catch (error) {
                console.log('Using sample data');
                this.loadSampleMarkers();
            }
        },

        loadSampleMarkers() {
            const samples = [
                { idpel: '53110001234', koordinat_x: -6.7166, koordinat_y: 108.5570, kdam: 'M', nama: 'Sample 1', nama_kabupaten: 'KOTA CIREBON' },
                { idpel: '53110005678', koordinat_x: -6.7066, koordinat_y: 108.5670, kdam: 'A', nama: 'Sample 2', nama_kabupaten: 'KOTA CIREBON' },
                { idpel: '53110009999', koordinat_x: -6.6966, koordinat_y: 108.5470, kdam: null, nama: 'Sample 3', nama_kabupaten: 'KAB. CIREBON' },
            ];
            samples.forEach(point => this.addMarker(point));
        },

        addMarker(point) {
            const color = point.kdam === 'M' ? '#17C353' : point.kdam === 'A' ? '#FBED21' : '#EB2027';
            let markerHtml;
            
            if (!point.kdam || (point.kdam !== 'M' && point.kdam !== 'A')) {
                // Diamond for unclear
                markerHtml = `<div style="width:12px;height:12px;background:${color};transform:rotate(45deg);border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>`;
            } else if (point.kdam === 'A') {
                // Triangle for abonemen
                markerHtml = `<div style="width:0;height:0;border-left:8px solid transparent;border-right:8px solid transparent;border-bottom:14px solid ${color};filter:drop-shadow(0 2px 2px rgba(0,0,0,0.3));"></div>`;
            } else {
                // Circle for meterisasi
                markerHtml = `<div style="width:14px;height:14px;background:${color};border-radius:50%;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>`;
            }

            const icon = L.divIcon({
                html: markerHtml,
                className: 'custom-marker',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });

            const marker = L.marker([point.koordinat_x, point.koordinat_y], { icon })
                .addTo(this.markerLayer);
            
            marker.on('click', () => {
                this.selectedPoint = point;
                this.map.panTo([point.koordinat_x, point.koordinat_y]);
            });

            this.markers.push({ marker, data: point });
        },

        closeDetail() {
            this.selectedPoint = null;
        },

        searchIdpel() {
            if (!this.searchQuery) return;
            const found = this.markers.find(m => m.data.idpel?.includes(this.searchQuery));
            if (found) {
                this.selectedPoint = found.data;
                this.map.setView([found.data.koordinat_x, found.data.koordinat_y], 16);
            }
        },

        filterByRegion(region) {
            console.log('Filter by region:', region);
        },

        filterByStatus(status) {
            console.log('Filter by status:', status);
        }
    };
}
</script>
<style>
.custom-marker { background: transparent !important; border: none !important; }
</style>
@endpush
@endsection
