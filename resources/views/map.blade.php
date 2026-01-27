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

                <!-- Photo Filter Toggle -->
                <button @click="togglePhotoFilter()"
                    class="bg-white rounded-lg shadow-lg px-4 py-2.5 flex items-center gap-2 text-sm hover:bg-gray-50 transition-colors"
                    :class="showOnlyWithPhoto ? 'text-[#29AAE1] ring-2 ring-[#29AAE1]' : 'text-gray-700'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-text="showOnlyWithPhoto ? 'With Photo' : 'Show All'"></span>
                </button>
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
                <div class="border-t mt-4 pt-3 space-y-2.5 text-sm">
                    <p class="text-gray-500 font-semibold">Wilayah</p>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-sm bg-[#B51CEC] opacity-60"></div>
                        <span class="text-gray-600">KAB. CIREBON</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-sm bg-[#29AAE1] opacity-60"></div>
                        <span class="text-gray-600">KOTA CIREBON</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-sm bg-[#17C353] opacity-60"></div>
                        <span class="text-gray-600">KAB. KUNINGAN</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-sm bg-[#FBED21] opacity-60"></div>
                        <span class="text-gray-600">KAB. MAJALENGKA</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-sm bg-[#EB2027] opacity-60"></div>
                        <span class="text-gray-600">KAB. INDRAMAYU</span>
                    </div>
                </div>
            </div>

            <!-- Focus Mode Blur Overlay - Only covers content area, not navbar/sidebar -->
            <div x-show="isFocused" x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" @click="closeDetail()"
                class="absolute inset-0 bg-black/30 backdrop-blur-sm z-[1001] cursor-pointer" style="pointer-events: auto;">
            </div>

            <!-- Popup on marker click -->
            <div x-show="hoveredPoint" x-transition
                class="fixed bg-white rounded-xl shadow-2xl z-[1003] min-w-[260px] max-w-[300px] overflow-hidden"
                :style="'left:' + popupX + 'px; top:' + popupY + 'px'" @click.stop>

                <!-- Image Carousel (like Google Maps) -->
                <div class="relative" x-show="relatedPhotos.length > 0">
                    <div class="w-full h-40 bg-gray-100 overflow-hidden">
                        <img :src="'/storage/' + relatedPhotos[currentPhotoIndex]?.photo" class="w-full h-full object-cover"
                            x-show="relatedPhotos[currentPhotoIndex]?.photo">
                    </div>
                    <!-- Prev/Next Buttons -->
                    <template x-if="relatedPhotos.length > 1">
                        <div>
                            <button @click.stop="prevPhoto()"
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white/80 hover:bg-white rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button @click.stop="nextPhoto()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white/80 hover:bg-white rounded-full flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <!-- Photo Counter -->
                            <div
                                class="absolute bottom-2 left-1/2 -translate-x-1/2 bg-black/50 text-white text-xs px-2 py-1 rounded-full">
                                <span x-text="(currentPhotoIndex + 1) + ' / ' + relatedPhotos.length"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- No Photo Placeholder -->
                <div x-show="relatedPhotos.length === 0"
                    class="w-full h-28 bg-gray-100 flex flex-col items-center justify-center gap-1">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-gray-400 text-sm font-medium">Not Visually Identified</span>
                    <span class="text-gray-300 text-[10px]">Unable to identify via Street View</span>
                </div>

                <!-- Popup Content -->
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-5 h-5 border-2 rounded-full"
                            :class="hoveredPoint?.kdam === 'M' ? 'border-[#17C353]' : hoveredPoint?.kdam === 'A' ? 'border-[#FBED21]' : 'border-[#EB2027]'">
                        </div>
                        <span class="font-bold text-gray-800">ID Pel - <span x-text="hoveredPoint?.idpel"></span></span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2" x-text="hoveredPoint?.nama_kabupaten"></p>
                    <div class="flex justify-between text-sm mb-3">
                        <div>
                            <p class="text-gray-500">Jumlah</p>
                            <p class="font-bold text-gray-800" x-text="getJumlahLampu(hoveredPoint?.idpel)"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-500">Status</p>
                            <p class="font-bold"
                                :class="hoveredPoint?.kdam === 'M' ? 'text-[#17C353]' : hoveredPoint?.kdam === 'A' ? 'text-[#FBED21]' : 'text-[#EB2027]'"
                                x-text="hoveredPoint?.kdam === 'M' ? 'Meterisasi' : hoveredPoint?.kdam === 'A' ? 'Abonemen' : 'Unclear'">
                            </p>
                        </div>
                    </div>
                    <!-- Quick Google Maps Link -->
                    <a :href="'https://www.google.com/maps?q=' + hoveredPoint?.koordinat_x + ',' + hoveredPoint?.koordinat_y"
                        target="_blank" rel="noopener noreferrer"
                        class="flex items-center justify-center gap-1.5 w-full bg-[#29AAE1] hover:bg-[#1E8CC0] text-white py-2 px-3 rounded-lg text-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        View in Google Maps
                    </a>
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
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-800">View <span x-text="selectedPoint?.idpel"></span></span>
                    <span x-show="selectedPoint?.is_idpel_main"
                        class="px-2 py-0.5 bg-green-100 text-green-800 text-xs font-bold rounded-full flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        MAIN
                    </span>
                </div>
                <button @click="closeDetail()" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
            </div>

            <!-- Photo Section - Single photo per location -->
            <div class="relative h-44 bg-gray-100 flex items-center justify-center overflow-hidden">
                <template x-if="selectedPoint?.photo">
                    <img :src="'/storage/' + selectedPoint.photo" class="w-full h-full object-cover"
                        :alt="'Photo ' + selectedPoint.idpel">
                </template>
                <template x-if="!selectedPoint?.photo">
                    <div class="flex flex-col items-center gap-2 text-gray-400 p-4 text-center">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-sm font-medium">Not Visually Identified</span>
                        <span class="text-xs text-gray-300">Unable to identify via Street View</span>
                    </div>
                </template>
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
                        <td class="text-[#29AAE1]" x-text="getWilayahDishub(selectedPoint)"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Alamat</td>
                        <td>:</td>
                        <td class="text-[#29AAE1]" x-text="getAlamat(selectedPoint)"></td>
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
                        <td class="text-[#29AAE1]" x-text="getNoMeter(selectedPoint)"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Jumlah Lampu</td>
                        <td>:</td>
                        <td class="text-[#EB2027] font-bold" x-text="getJumlahLampu(selectedPoint?.idpel)"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Data Gardu</td>
                        <td>:</td>
                        <td class="text-[#29AAE1]" x-text="getGardu(selectedPoint)"></td>
                    </tr>
                    <tr class="h-8">
                        <td class="text-gray-600">Titik Koordinat</td>
                        <td>:</td>
                        <td>
                            <a :href="'https://www.google.com/maps?q=' + selectedPoint?.koordinat_x + ',' + selectedPoint?.koordinat_y"
                                target="_blank" rel="noopener noreferrer"
                                class="font-mono text-xs text-[#29AAE1] hover:underline cursor-pointer"
                                x-text="(selectedPoint?.koordinat_x || '-') + ', ' + (selectedPoint?.koordinat_y || '-')">
                            </a>
                        </td>
                    </tr>
                </table>

                <!-- Google Maps Button -->
                <div class="mt-4 pt-4 border-t">
                    <a :href="'https://www.google.com/maps/place/' + selectedPoint?.koordinat_x + ',' + selectedPoint?.koordinat_y + '/@' + selectedPoint?.koordinat_x + ',' + selectedPoint?.koordinat_y + ',18z'"
                        target="_blank" rel="noopener noreferrer"
                        class="flex items-center justify-center gap-2 w-full bg-[#29AAE1] hover:bg-[#1E8CC0] text-white py-2.5 px-4 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>View in Google Maps</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
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
                    hoveredPoint: null,
                    hoveredLatLng: null,
                    isFocused: false,
                    currentPhotoIndex: 0,
                    relatedPhotos: [],
                    popupX: 0,
                    popupY: 0,
                    searchQuery: '',
                    selectedRegion: null,
                    selectedStatus: null,
                    showOnlyWithPhoto: true,
                    allMarkersData: [],
                    regions: [
                        { name: 'KAB. CIREBON', label: 'Kab. Cirebon', color: '#B51CEC' },
                        { name: 'KOTA CIREBON', label: 'Kota Cirebon', color: '#29AAE1' },
                        { name: 'KAB. INDRAMAYU', label: 'Indramayu', color: '#EB2027' },
                        { name: 'MAJALENGKA', label: 'Majalengka', color: '#FBED21' },
                        { name: 'KAB. KUNINGAN', label: 'Kuningan', color: '#17C353' }
                    ],
                    idpelCounts: {},

                    // Helper function: Get formatted address
                    getAlamat(point) {
                        if (!point) return '-';
                        const parts = [point.namapnj, point.rt ? `RT ${point.rt}` : null, point.rw ? `RW ${point.rw}` : null, point.nama_kecamatan, point.nama_kelurahan].filter(Boolean);
                        return parts.length ? parts.join(', ') : '-';
                    },

                    // Helper function: Get no meter info
                    getNoMeter(point) {
                        if (!point) return '-';
                        const jenis = point.jenislayanan || '';
                        const kwh = point.nomor_meter_kwh || '';
                        const prepaid = point.nomor_meter_prepaid || '';
                        if (prepaid) return `PRABAYAR (${prepaid})`;
                        if (kwh) return `PASKABAYAR (${kwh})`;
                        if (jenis) return jenis;
                        return '-';
                    },

                    // Helper function: Get gardu info
                    getGardu(point) {
                        if (!point) return '-';
                        const parts = [point.nomor_gardu, point.nama_gardu, point.nomor_jurusan_tiang].filter(Boolean);
                        return parts.length ? parts.join(' / ') : '-';
                    },

                    // Helper function: Get Wilayah Dishub with proper naming
                    getWilayahDishub(point) {
                        if (!point || !point.nama_kabupaten) return '-';
                        let wilayah = point.nama_kabupaten.toUpperCase();

                        // Map Cilimus to Kab. Cirebon
                        if (wilayah.includes('CILIMUS')) {
                            return 'KAB. CIREBON';
                        }

                        // Return the full name as-is from database
                        return wilayah;
                    },

                    // Helper function: Get jumlah lampu (count of markers with same IDPEL)
                    getJumlahLampu(idpel) {
                        return this.idpelCounts[idpel] || 1;
                    },

                    async init() {
                        // Prevent double initialization - check if map already exists
                        if (this.map) {
                            console.log('Map already initialized, skipping');
                            return;
                        }

                        // Also check if container already has a map
                        const container = document.getElementById('main-map');
                        if (container && container._leaflet_id) {
                            console.log('Container already has map, skipping');
                            return;
                        }

                        this.map = L.map('main-map', { zoomControl: true }).setView([-6.7320, 108.5523], 11);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(this.map);

                        // Use regular FeatureGroup instead of MarkerCluster for accurate positioning
                        // Markers will stay at exact coordinates without shifting
                        this.markerLayer = L.featureGroup().addTo(this.map);
                        this.lineLayer = L.featureGroup().addTo(this.map);

                        this.addRegionalOverlays(); // Load polygons (async, runs in background)
                        await this.loadMarkers();
                        // Debounced reload on map move/zoom for viewport-based loading
                        let reloadTimeout;
                        this.map.on('moveend zoomend', () => {
                            clearTimeout(reloadTimeout);
                            reloadTimeout = setTimeout(() => {
                                this.reloadViewportMarkers();
                            }, 300); // Wait 300ms after map stops moving
                        });

                        // Update popup position when map moves to keep it attached to marker
                        this.map.on('move zoom', () => {
                            this.updatePopupPosition();
                        });
                    },

                    updatePopupPosition() {
                        if (this.hoveredLatLng && this.map) {
                            const point = this.map.latLngToContainerPoint(this.hoveredLatLng);
                            this.popupX = point.x + 20;
                            this.popupY = point.y - 50;
                        }
                    },

                    async addRegionalOverlays() {
                        // Define colors for each region
                        const regionColors = {
                            'KOTA CIREBON': '#29AAE1',
                            'KAB. CIREBON': '#B51CEC',
                            'KAB. INDRAMAYU': '#EB2027',
                            'MAJALENGKA': '#FBED21',
                            'KAB. KUNINGAN': '#17C353',
                            'KAB. MAJALENGKA': '#FBED21'
                        };

                        try {
                            // Fetch region bounds from API
                            console.log('Fetching region bounds...');
                            const response = await fetch('/api/region-bounds');
                            const regions = await response.json();
                            console.log('Regions loaded:', regions.length, 'regions');

                            regions.forEach(region => {
                                // Skip regions with empty name or too few points
                                if (!region.name || region.name.trim() === '' || region.points.length < 1) return;

                                // Create convex hull polygon from all points
                                const polygon = this.getConvexHull(region.points);
                                console.log('Region:', region.name, '- Points:', region.points.length, '- Polygon:', polygon.length);
                                if (polygon.length < 4) return;

                                const color = regionColors[region.name] || '#888888';

                                L.polygon(polygon, {
                                    color: color,
                                    weight: 2,
                                    fillColor: color,
                                    fillOpacity: 0.15
                                }).bindTooltip(region.name + ' (' + region.count + ' titik)', {
                                    permanent: false
                                }).addTo(this.map);
                            });
                        } catch (e) {
                            console.error('Error loading region overlays:', e);
                        }
                    },
                    // Monotone Chain Convex Hull Algorithm - follows outer boundary of points
                    getConvexHull(points) {
                        if (points.length < 3) return points;

                        // Clone and sort points by x, then y
                        const sorted = points.map(p => [...p]).sort((a, b) =>
                            a[1] === b[1] ? a[0] - b[0] : a[1] - b[1]
                        );

                        // Cross product helper
                        const cross = (o, a, b) =>
                            (a[1] - o[1]) * (b[0] - o[0]) - (a[0] - o[0]) * (b[1] - o[1]);

                        // Build lower hull
                        const lower = [];
                        for (const p of sorted) {
                            while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) {
                                lower.pop();
                            }
                            lower.push(p);
                        }

                        // Build upper hull
                        const upper = [];
                        for (let i = sorted.length - 1; i >= 0; i--) {
                            const p = sorted[i];
                            while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], p) <= 0) {
                                upper.pop();
                            }
                            upper.push(p);
                        }

                        // Remove last point of each half (it's the starting point of the other)
                        lower.pop();
                        upper.pop();

                        // Concatenate to form full hull
                        return lower.concat(upper);
                    },

                    async loadMarkers() {
                        try {
                            // Get current viewport bounds for optimized loading
                            const bounds = this.map.getBounds();
                            const params = new URLSearchParams({
                                limit: 5000,
                                minLat: bounds.getSouth(),
                                maxLat: bounds.getNorth(),
                                minLng: bounds.getWest(),
                                maxLng: bounds.getEast(),
                                withPhoto: this.showOnlyWithPhoto ? '1' : '0'
                            });

                            const response = await fetch(`/api/pju-markers?${params.toString()}`);
                            const data = await response.json();

                            // Count IDPEL occurrences for Jumlah Lampu
                            this.idpelCounts = {};
                            data.forEach(p => {
                                if (p.idpel) {
                                    this.idpelCounts[p.idpel] = (this.idpelCounts[p.idpel] || 0) + 1;
                                }
                            });

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
                                    // Group by IDPEL for connecting lines (store coords, is_idpel_main, and kdam)
                                    if (p.idpel) {
                                        if (!idpelGroups[p.idpel]) idpelGroups[p.idpel] = [];
                                        idpelGroups[p.idpel].push({
                                            coords: [lat, lng],
                                            is_idpel_main: p.is_idpel_main,
                                            kdam: p.kdam
                                        });
                                    }
                                }
                            });

                            // Draw connecting lines for same IDPEL (multiple lamps)
                            this.drawConnectingLines(idpelGroups);
                        } catch (e) {
                            console.error('Error loading markers:', e);
                        }
                    },

                    async reloadViewportMarkers() {
                        // Clear existing markers and lines
                        this.markerLayer.clearLayers();
                        if (this.lineLayer) {
                            this.lineLayer.clearLayers();
                        }
                        // Keep cached data for search and don't clear allMarkers
                        // Reload markers for current viewport
                        await this.loadMarkers();
                    },

                    async togglePhotoFilter() {
                        this.showOnlyWithPhoto = !this.showOnlyWithPhoto;
                        await this.reloadViewportMarkers();
                    },

                    drawConnectingLines(idpelGroups) {
                        Object.entries(idpelGroups).forEach(([idpel, points]) => {
                            if (points.length > 1) {
                                // Find IDPEL Main point (center hub)
                                const mainPoint = points.find(p => p.is_idpel_main);
                                const branchPoints = points.filter(p => !p.is_idpel_main);

                                // Get line color from status (kdam) - use first point's status
                                const kdam = points[0]?.kdam || 'M';
                                const lineColor = kdam === 'M' ? '#17C353' : kdam === 'A' ? '#FBED21' : '#EB2027';

                                if (mainPoint && branchPoints.length > 0) {
                                    // Star pattern: Connect IDPEL Main to each branch
                                    branchPoints.forEach(branch => {
                                        L.polyline([mainPoint.coords, branch.coords], {
                                            color: lineColor,
                                            weight: 2,
                                            opacity: 0.8
                                        }).addTo(this.markerLayer);
                                    });
                                } else {
                                    // Fallback: Connect all points in chain
                                    const coords = points.map(p => p.coords);
                                    L.polyline(coords, {
                                        color: lineColor,
                                        weight: 2,
                                        opacity: 0.8
                                    }).addTo(this.markerLayer);
                                }
                            }
                        });
                    },

                    loadSampleMarkers() {
                        // Sample data - will be replaced by real database data
                    },

                    addMarker(point) {
                        const color = point.kdam === 'M' ? '#17C353' : point.kdam === 'A' ? '#FBED21' : '#EB2027';
                        let html;

                        // IDPEL Main (with gardu) - circle outline
                        if (point.is_idpel_main) {
                            html = `<div style="width:16px;height:16px;background:transparent;border-radius:50%;border:3px solid ${color};"></div>`;
                        }
                        // Unclear status - star shape
                        else if (!point.kdam || (point.kdam !== 'M' && point.kdam !== 'A')) {
                            html = `<svg width="16" height="16" viewBox="0 0 24 24" fill="${color}"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>`;
                        }
                        // Abonemen - triangle
                        else if (point.kdam === 'A') {
                            html = `<div style="width:0;height:0;border-left:8px solid transparent;border-right:8px solid transparent;border-bottom:14px solid ${color};"></div>`;
                        }
                        // Meterisasi - filled circle
                        else {
                            html = `<div style="width:14px;height:14px;background:${color};border-radius:50%;border:2px solid white;"></div>`;
                        }
                        const icon = L.divIcon({ html, className: 'custom-marker', iconSize: [16, 16], iconAnchor: [8, 8] });
                        const marker = L.marker([point.koordinat_x, point.koordinat_y], { icon }).addTo(this.markerLayer);

                        marker.on('click', (e) => {
                            this.selectedPoint = point;
                            this.hoveredPoint = point;
                            this.hoveredLatLng = L.latLng(point.koordinat_x, point.koordinat_y);
                            this.isFocused = true;
                            // Position popup near center of map container, not following mouse
                            const mapContainer = document.getElementById('main-map');
                            const rect = mapContainer.getBoundingClientRect();
                            this.popupX = Math.min(Math.max(e.containerPoint.x + 20, 20), rect.width - 320);
                            this.popupY = Math.min(Math.max(e.containerPoint.y - 100, 20), rect.height - 350);
                            this.loadRelatedPhotos(point.idpel);
                            // Don't panTo - let user see marker without jumping
                        });

                        // Popup only appears on click, not hover

                        this.markers.push({ marker, data: point });
                    },

                    loadRelatedPhotos(idpel) {
                        const clickedPoint = this.hoveredPoint;

                        // If clicked point is IDPEL Main, show carousel with all photos from same IDPEL
                        if (clickedPoint && clickedPoint.is_idpel_main) {
                            this.relatedPhotos = this.markers
                                .filter(m => m.data.idpel === idpel && m.data.photo)
                                .map(m => ({
                                    photo: m.data.photo,
                                    koordinat_x: m.data.koordinat_x,
                                    koordinat_y: m.data.koordinat_y,
                                    is_idpel_main: m.data.is_idpel_main
                                }));
                        } else {
                            // For non-main markers, show only this point's photo
                            if (clickedPoint && clickedPoint.photo) {
                                this.relatedPhotos = [{
                                    photo: clickedPoint.photo,
                                    koordinat_x: clickedPoint.koordinat_x,
                                    koordinat_y: clickedPoint.koordinat_y,
                                    is_idpel_main: clickedPoint.is_idpel_main
                                }];
                            } else {
                                this.relatedPhotos = [];
                            }
                        }
                        this.currentPhotoIndex = 0;
                    },

                    nextPhoto() {
                        if (this.relatedPhotos.length > 0) {
                            this.currentPhotoIndex = (this.currentPhotoIndex + 1) % this.relatedPhotos.length;
                        }
                    },

                    prevPhoto() {
                        if (this.relatedPhotos.length > 0) {
                            this.currentPhotoIndex = (this.currentPhotoIndex - 1 + this.relatedPhotos.length) % this.relatedPhotos.length;
                        }
                    },

                    closeDetail() {
                        this.selectedPoint = null;
                        this.hoveredPoint = null;
                        this.isFocused = false;
                        this.hoveredLatLng = null;
                    },

                    async searchIdpel() {
                        if (!this.searchQuery) return;

                        // First try to find in already loaded markers
                        let found = this.markers.find(m => m.data.idpel === this.searchQuery) ||
                            this.markers.find(m => m.data.idpel?.includes(this.searchQuery));

                        // If not found locally, search from server
                        if (!found) {
                            try {
                                const response = await fetch(`/api/pju-markers/search?q=${encodeURIComponent(this.searchQuery)}`);
                                const data = await response.json();

                                if (data.length > 0) {
                                    // Add searched markers to map
                                    data.forEach(p => {
                                        const lat = parseFloat(p.koordinat_x);
                                        const lng = parseFloat(p.koordinat_y);
                                        if (!isNaN(lat) && !isNaN(lng)) {
                                            p.koordinat_x = lat;
                                            p.koordinat_y = lng;
                                            // Check if marker already exists
                                            const exists = this.markers.find(m =>
                                                m.data.idpel === p.idpel &&
                                                m.data.koordinat_x === lat &&
                                                m.data.koordinat_y === lng
                                            );
                                            if (!exists) {
                                                this.addMarker(p);
                                                // Update IDPEL counts
                                                this.idpelCounts[p.idpel] = (this.idpelCounts[p.idpel] || 0) + 1;
                                            }
                                        }
                                    });
                                    // Now find the marker
                                    found = this.markers.find(m => m.data.idpel === this.searchQuery) ||
                                        this.markers.find(m => m.data.idpel?.includes(this.searchQuery));
                                }
                            } catch (e) {
                                console.error('Search error:', e);
                            }
                        }

                        if (found) {
                            this.selectedPoint = found.data;
                            this.hoveredPoint = found.data;
                            this.hoveredLatLng = L.latLng(found.data.koordinat_x, found.data.koordinat_y);
                            this.isFocused = true;
                            // Center popup on screen
                            const mapContainer = document.getElementById('main-map');
                            const rect = mapContainer.getBoundingClientRect();
                            this.popupX = rect.width / 2 - 130;
                            this.popupY = 100;
                            this.loadRelatedPhotos(found.data.idpel);
                            this.map.setView([found.data.koordinat_x, found.data.koordinat_y], 16);
                        } else {
                            alert('IDPEL tidak ditemukan: ' + this.searchQuery);
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

            /* Fix map drag issue */
            #main-map {
                z-index: 1;
            }

            .leaflet-container {
                z-index: 1 !important;
            }

            .marker-cluster {
                background-clip: padding-box;
            }

            .marker-cluster div {
                background-color: rgba(41, 170, 225, 0.6);
            }

            .marker-cluster span {
                color: #fff;
                font-weight: bold;
            }
        </style>
    @endpush
@endsection