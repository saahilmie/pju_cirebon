@extends('layouts.app')

@section('title', 'PJU Report')
@section('page-title', 'PJU Report')

@section('content')
    <div x-data="pjuReport()" x-init="init()" class="p-6">
        <!-- Toast Notification -->
        <div x-show="toast.show" x-cloak x-transition class="fixed top-20 right-6 z-50">
            <div class="px-4 py-3 rounded-lg shadow-lg text-white"
                :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'">
                <span x-text="toast.message"></span>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-800 mb-4">PJU Report</h2>

        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6" style="border: 1px solid #C8BFBF;">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="selectAll()" class="px-5 py-2 rounded-full text-sm font-medium transition-all"
                        :class="isAllSelected ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700'">All</button>

                    <!-- Regional Filter -->
                    <div class="relative">
                        <button @click="toggleDropdown('regional')"
                            class="px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2"
                            :class="selectedRegionals.length > 0 ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700'">
                            Regional
                            <span x-show="selectedRegionals.length > 0"
                                class="bg-white text-[#29AAE1] text-xs font-bold px-1.5 rounded-full"
                                x-text="selectedRegionals.length"></span>
                            <template x-if="selectedRegionals.length > 0">
                                <span @click.stop="clearFilter('regional')" class="ml-1 cursor-pointer">&times;</span>
                            </template>
                            <template x-if="selectedRegionals.length === 0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </template>
                        </button>
                        <div x-show="activeDropdown === 'regional'" @click.away="activeDropdown = null" x-transition
                            class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-xl z-50 w-64"
                            style="border: 1px solid #C8BFBF;">
                            <div class="p-3" style="border-bottom: 1px solid #C8BFBF;">
                                <input type="text" x-model="searchRegional" placeholder="Search..."
                                    class="w-full px-3 py-2 rounded-lg text-sm focus:outline-none"
                                    style="border: 1px solid #C8BFBF;">
                            </div>
                            <div class="max-h-48 overflow-y-auto p-2">
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" :checked="selectedRegionals.length === regionals.length"
                                        @change="toggleAllRegionals()" class="w-4 h-4 rounded text-[#29AAE1]"
                                        style="border-color: #C8BFBF;">
                                    <span class="font-medium">All Regionals</span>
                                    <span class="text-gray-400 text-sm ml-auto">(<span
                                            x-text="regionals.length"></span>)</span>
                                </label>
                                <template x-for="r in filteredRegionals" :key="r">
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer pl-6">
                                        <input type="checkbox" :value="r" x-model="selectedRegionals"
                                            class="w-4 h-4 rounded text-[#29AAE1]" style="border-color: #C8BFBF;">
                                        <span x-text="r" class="text-sm"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="p-3" style="border-top: 1px solid #C8BFBF;">
                                <button @click="applyFilter(); activeDropdown = null"
                                    class="w-full bg-[#29AAE1] text-white py-2 rounded-lg font-medium">Apply</button>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <button @click="toggleDropdown('status')"
                            class="px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2"
                            :class="selectedStatuses.length > 0 ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700'">
                            Status
                            <span x-show="selectedStatuses.length > 0"
                                class="bg-white text-[#29AAE1] text-xs font-bold px-1.5 rounded-full"
                                x-text="selectedStatuses.length"></span>
                            <template x-if="selectedStatuses.length > 0">
                                <span @click.stop="clearFilter('status')" class="ml-1 cursor-pointer">&times;</span>
                            </template>
                            <template x-if="selectedStatuses.length === 0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </template>
                        </button>
                        <div x-show="activeDropdown === 'status'" @click.away="activeDropdown = null" x-transition
                            class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-xl z-50 w-56"
                            style="border: 1px solid #C8BFBF;">
                            <div class="p-3" style="border-bottom: 1px solid #C8BFBF;">
                                <input type="text" x-model="searchStatus" placeholder="Search..."
                                    class="w-full px-3 py-2 rounded-lg text-sm focus:outline-none"
                                    style="border: 1px solid #C8BFBF;">
                            </div>
                            <div class="max-h-48 overflow-y-auto p-2">
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" :checked="selectedStatuses.length === statuses.length"
                                        @change="toggleAllStatuses()" class="w-4 h-4 rounded text-[#29AAE1]"
                                        style="border-color: #C8BFBF;">
                                    <span class="font-medium">All Status</span>
                                    <span class="text-gray-400 text-sm ml-auto">(<span
                                            x-text="statuses.length"></span>)</span>
                                </label>
                                <template x-for="s in filteredStatuses" :key="s">
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer pl-6">
                                        <input type="checkbox" :value="s" x-model="selectedStatuses"
                                            class="w-4 h-4 rounded text-[#29AAE1]" style="border-color: #C8BFBF;">
                                        <span x-text="s" class="text-sm"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="p-3" style="border-top: 1px solid #C8BFBF;">
                                <button @click="applyFilter(); activeDropdown = null"
                                    class="w-full bg-[#29AAE1] text-white py-2 rounded-lg font-medium">Apply</button>
                            </div>
                        </div>
                    </div>

                    <!-- IDPEL Filter -->
                    <div class="relative">
                        <button @click="toggleDropdown('idpel')"
                            class="px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2"
                            :class="selectedIdpels.length > 0 ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700'">
                            IDPEL
                            <span x-show="selectedIdpels.length > 0"
                                class="bg-white text-[#29AAE1] text-xs font-bold px-1.5 rounded-full"
                                x-text="selectedIdpels.length"></span>
                            <template x-if="selectedIdpels.length > 0">
                                <span @click.stop="clearFilter('idpel')" class="ml-1 cursor-pointer">&times;</span>
                            </template>
                            <template x-if="selectedIdpels.length === 0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </template>
                        </button>
                        <div x-show="activeDropdown === 'idpel'" @click.away="activeDropdown = null" x-transition
                            class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-xl z-50 w-64"
                            style="border: 1px solid #C8BFBF;">
                            <div class="p-3" style="border-bottom: 1px solid #C8BFBF;">
                                <input type="text" x-model="searchIdpel" placeholder="Search..."
                                    class="w-full px-3 py-2 rounded-lg text-sm focus:outline-none"
                                    style="border: 1px solid #C8BFBF;">
                            </div>
                            <div class="max-h-48 overflow-y-auto p-2">
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" :checked="selectedIdpels.length === idpels.length"
                                        @change="toggleAllIdpels()" class="w-4 h-4 rounded text-[#29AAE1]"
                                        style="border-color: #C8BFBF;">
                                    <span class="font-medium">All IDPELs</span>
                                    <span class="text-gray-400 text-sm ml-auto">(<span
                                            x-text="idpels.length"></span>)</span>
                                </label>
                                <template x-for="i in filteredIdpels.slice(0, 50)" :key="i">
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer pl-6">
                                        <input type="checkbox" :value="i" x-model="selectedIdpels"
                                            class="w-4 h-4 rounded text-[#29AAE1]" style="border-color: #C8BFBF;">
                                        <span x-text="i" class="text-sm font-mono"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="p-3" style="border-top: 1px solid #C8BFBF;">
                                <button @click="applyFilter(); activeDropdown = null"
                                    class="w-full bg-[#29AAE1] text-white py-2 rounded-lg font-medium">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
                <button x-show="hasActiveFilters" @click="clearAllFilters()"
                    class="text-gray-500 hover:text-red-500 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    CLEAR ALL
                </button>
            </div>
        </div>

        <!-- Report Content -->
        <div class="bg-white rounded-xl shadow-sm" style="border: 1px solid #C8BFBF;">
            <div class="p-4 flex items-center justify-between" style="border-bottom: 1px solid #C8BFBF;">
                <h3 class="text-lg font-bold text-gray-800">All Report</h3>
                <div class="flex items-center gap-3">
                    <div class="relative flex items-center gap-1">
                        <input type="text" placeholder="Search IDPEL (min 3 chars, Enter to search)" x-model="searchTable"
                            @keyup.enter="searchServer()"
                            class="px-4 py-2 rounded-lg text-sm w-80 focus:outline-none focus:ring-2 focus:ring-[#29AAE1]"
                            style="border: 1px solid #C8BFBF;">
                        <button @click="searchServer()"
                            class="px-3 py-2 bg-[#29AAE1] text-white rounded-lg text-sm hover:bg-[#1E8CC0]"
                            title="Search entire database">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                        <button x-show="searchTable" @click="searchTable = ''; currentSearch = ''; loadData()"
                            class="px-2 py-2 text-gray-400 hover:text-gray-600 rounded-lg text-sm" title="Clear search">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Hidden file inputs for import -->
                    <input type="file" id="importFileCSV" accept=".csv" @change="handleImport($event, 'csv')"
                        class="hidden">
                    <input type="file" id="importFileExcel" accept=".xlsx,.xls" @change="handleImport($event, 'excel')"
                        class="hidden">

                    @if(auth()->user() && auth()->user()->isAdmin())
                        <!-- Import Dropdown - Admin only -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                class="px-4 py-2 rounded-lg text-sm flex items-center gap-2 hover:bg-gray-50"
                                style="border: 1px solid #C8BFBF;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Import
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute top-full right-0 mt-1 bg-white rounded-lg shadow-xl z-50 w-40 py-1"
                                style="border: 1px solid #C8BFBF;">
                                <button @click="document.getElementById('importFileExcel').click(); open = false"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Excel (.xlsx)
                                </button>
                                <button @click="document.getElementById('importFileCSV').click(); open = false"
                                    class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    CSV (.csv)
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Export Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="px-4 py-2 rounded-lg text-sm flex items-center gap-2 hover:bg-gray-50"
                            style="border: 1px solid #C8BFBF;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute top-full right-0 mt-1 bg-white rounded-lg shadow-xl z-50 w-40 py-1"
                            style="border: 1px solid #C8BFBF;">
                            <button @click="exportData('excel'); open = false"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Excel (.xlsx)
                            </button>
                            <button @click="exportData('csv'); open = false"
                                class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                CSV (.csv)
                            </button>
                        </div>
                    </div>
                    @if(auth()->user() && auth()->user()->isAdmin())
                        <button @click="openAddModal()"
                            class="px-4 py-2 bg-[#29AAE1] text-white rounded-lg text-sm flex items-center gap-2 hover:bg-[#1E8CC0]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Data
                        </button>
                    @endif
                </div>
            </div>

            <!-- Table with horizontal scroll -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1800px]">
                    <thead class="bg-[#29AAE1] text-white">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NO</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">IDPEL</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NAMA</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NAMAPNJ</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">RT</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">RW</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">TARIF</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">DAYA</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">JENISLAYANAN</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NO_METER_KWH</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NO_GARDU</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NO_JRS_TIANG</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NAMA_GARDU</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">NO_METER_PREPAID</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">KOORDINAT_X</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">KOORDINAT_Y</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">KDAM</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">KABUPATEN</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">KECAMATAN</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold">KELURAHAN</th>
                            <th class="px-3 py-3 text-left text-xs font-semibold sticky right-0 bg-[#29AAE1]">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in filteredData" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-3 text-xs text-gray-600"
                                    x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="px-3 py-3 text-xs text-gray-800 font-medium" x-text="item.idpel"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nama || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.namapnj || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.rt || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.rw || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.tarif || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.daya || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.jenis_layanan || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nomor_meter_kwh || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nomor_gardu || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nomor_jurusan_tiang || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nama_gardu || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nomor_meter_prepaid || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600 font-mono" x-text="item.koordinat_x || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600 font-mono" x-text="item.koordinat_y || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.kdam || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nama_kabupaten || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nama_kecamatan || '-'"></td>
                                <td class="px-3 py-3 text-xs text-gray-600" x-text="item.nama_kelurahan || '-'"></td>
                                <td class="px-3 py-3 text-xs sticky right-0 bg-white">
                                    <div class="flex items-center gap-2">
                                        <button @click="viewItem(item)" class="text-gray-400 hover:text-[#29AAE1]"
                                            title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        @if(auth()->user() && auth()->user()->isAdmin())
                                            <button @click="copyAsNew(item)" class="text-gray-400 hover:text-green-500"
                                                title="Copy as New (same IDPEL, new location)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                            <button @click="openEditModal(item)" class="text-gray-400 hover:text-yellow-500"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="openDeleteModal(item)" class="text-gray-400 hover:text-red-500"
                                                title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @else
                                            <button @click="openPhotoUploadModal(item)"
                                                class="text-gray-400 hover:text-[#29AAE1]" title="Upload Photo">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredData.length === 0">
                            <td colspan="21" class="px-4 py-8 text-center text-gray-400">No data found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 flex items-center justify-end gap-2" style="border-top: 1px solid #C8BFBF;">
                <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                    class="px-3 py-1 rounded text-sm disabled:opacity-50" style="border: 1px solid #C8BFBF;">&lt;</button>
                <template x-for="page in visiblePages" :key="page">
                    <button @click="currentPage = page" class="px-3 py-1 rounded text-sm"
                        :class="currentPage === page ? 'bg-[#29AAE1] text-white' : ''"
                        :style="currentPage !== page && 'border: 1px solid #C8BFBF'" x-text="page"></button>
                </template>
                <span x-show="totalPages > 5" class="text-gray-400">...</span>
                <button x-show="totalPages > 5" @click="currentPage = totalPages" class="px-3 py-1 rounded text-sm"
                    :class="currentPage === totalPages && 'bg-[#29AAE1] text-white'" x-text="totalPages"></button>
                <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages"
                    class="px-3 py-1 rounded text-sm disabled:opacity-50" style="border: 1px solid #C8BFBF;">&gt;</button>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            x-transition>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto"
                @click.away="showModal = false">
                <div class="flex items-center justify-between p-6" style="border-bottom: 1px solid #C8BFBF;">
                    <h3 class="text-xl font-bold text-gray-800" x-text="isEditing ? 'Edit PJU Data' : 'Add New PJU Data'">
                    </h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <form @submit.prevent="saveData()" class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">IDPEL</label><input type="text"
                                x-model="form.idpel" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;" required></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NOMOR_JURUSAN_TIANG</label><input
                                type="text" x-model="form.nomor_jurusan_tiang" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NAMA</label><input type="text"
                                x-model="form.nama" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NAMA_GARDU</label><input
                                type="text" x-model="form.nama_gardu" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NAMAPNJ</label><input type="text"
                                x-model="form.namapnj" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NOMOR_METER_PREPAID</label><input
                                type="text" x-model="form.nomor_meter_prepaid" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div class="grid grid-cols-2 gap-2">
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">RT</label><input type="text"
                                    x-model="form.rt" class="w-full px-3 py-2 rounded-lg text-sm"
                                    style="border: 1px solid #C8BFBF;"></div>
                            <div><label class="block text-xs font-medium text-gray-600 mb-1">RW</label><input type="text"
                                    x-model="form.rw" class="w-full px-3 py-2 rounded-lg text-sm"
                                    style="border: 1px solid #C8BFBF;"></div>
                        </div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">KOORDINAT_X</label><input
                                type="text" x-model="form.koordinat_x" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">TARIF</label><input type="text"
                                x-model="form.tarif" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">KOORDINAT_Y</label><input
                                type="text" x-model="form.koordinat_y" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">DAYA</label><input type="text"
                                x-model="form.daya" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NAMA_KECAMATAN</label><input
                                type="text" x-model="form.nama_kecamatan" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NOMOR_METER_KWH</label><input
                                type="text" x-model="form.nomor_meter_kwh" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NAMA_KELURAHAN</label><input
                                type="text" x-model="form.nama_kelurahan" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">NOMOR_GARDU</label><input
                                type="text" x-model="form.nomor_gardu" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;"></div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">KDAM</label>
                            <div class="flex gap-4 mt-2">
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.kdam" value="M"
                                        class="text-[#29AAE1]"> M</label>
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.kdam" value="A"
                                        class="text-[#29AAE1]"> A</label>
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.kdam" value=""
                                        class="text-[#29AAE1]"> Unclear</label>
                            </div>
                        </div>
                        <div><label class="block text-xs font-medium text-gray-600 mb-1">JENISLAYANAN</label>
                            <select x-model="form.jenislayanan" class="w-full px-3 py-2 rounded-lg text-sm"
                                style="border: 1px solid #C8BFBF;">
                                <option value="">Select</option>
                                <option value="PRABAYAR">PRABAYAR</option>
                                <option value="PASKABAYAR">PASKABAYAR</option>
                            </select>
                        </div>
                        <div class="col-span-2"><label
                                class="block text-xs font-medium text-gray-600 mb-1">NAMA_KABUPATEN</label>
                            <div class="flex flex-wrap gap-4 mt-2">
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.nama_kabupaten"
                                        value="KAB. CIREBON" class="text-[#29AAE1]"> Kab. Cirebon</label>
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.nama_kabupaten"
                                        value="KOTA CIREBON" class="text-[#29AAE1]"> Kota Cirebon</label>
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.nama_kabupaten"
                                        value="KAB. KUNINGAN" class="text-[#29AAE1]"> Kab. Kuningan</label>
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.nama_kabupaten"
                                        value="MAJALENGKA" class="text-[#29AAE1]"> Majalengka</label>
                                <label class="flex items-center gap-2"><input type="radio" x-model="form.nama_kabupaten"
                                        value="KAB. INDRAMAYU" class="text-[#29AAE1]"> Indramayu</label>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="flex items-center gap-3 p-3 rounded-lg border transition-colors" :class="[
                                                        form.is_idpel_main ? 'border-[#29AAE1] bg-blue-50' : 'border-[#C8BFBF]',
                                                        hasExistingIdpelMain() && !form.is_idpel_main ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'
                                                    ]">
                                <input type="checkbox" x-model="form.is_idpel_main"
                                    :disabled="hasExistingIdpelMain() && !form.is_idpel_main"
                                    class="w-5 h-5 text-[#29AAE1] rounded focus:ring-[#29AAE1]">
                                <div>
                                    <span class="text-sm font-medium text-gray-700">IDPEL Main (Ada Gardu)</span>
                                    <p class="text-xs text-gray-500" x-show="!hasExistingIdpelMain() || form.is_idpel_main">
                                        Centang jika titik ini memiliki gardu/sumber listrik utama
                                    </p>
                                    <p class="text-xs text-red-500" x-show="hasExistingIdpelMain() && !form.is_idpel_main">
                                        IDPEL ini sudah ada yang ditandai sebagai IDPEL Main
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Photo Upload -->
                    <div class="mt-6">
                        <label class="block text-xs font-medium text-gray-600 mb-2">UPLOAD PJU DOCUMENTATION</label>
                        <div class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
                            :class="isDragging ? 'border-[#29AAE1] bg-blue-50' : 'border-[#C8BFBF]'"
                            @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)">
                            <template x-if="photoPreview">
                                <div class="flex items-center gap-4">
                                    <img :src="photoPreview" class="w-24 h-24 object-cover rounded-lg">
                                    <div class="flex-1 text-left">
                                        <p class="text-sm text-gray-600" x-text="photoName"></p>
                                        <button type="button" @click="removePhoto()"
                                            class="text-red-500 text-sm">Remove</button>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!photoPreview">
                                <div>
                                    <svg class="w-12 h-12 mx-auto text-[#29AAE1] mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-gray-600 mb-1">Drag and drop or click to upload image</p>
                                    <p class="text-gray-400 text-sm">Supports JPG, PNG (Max 20MB)</p>
                                    <input type="file" accept="image/jpeg,image/png" @change="handleFileSelect($event)"
                                        class="hidden" x-ref="fileInput">
                                    <button type="button" @click="$refs.fileInput.click()"
                                        class="mt-3 px-4 py-2 bg-[#29AAE1] text-white rounded-lg text-sm">Choose
                                        File</button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4" style="border-top: 1px solid #C8BFBF;">
                        <button type="button" @click="showModal = false" class="px-6 py-2 rounded-lg"
                            style="border: 1px solid #C8BFBF;">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-[#29AAE1] text-white rounded-lg"
                            x-text="isEditing ? 'Edit' : 'Add'"></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            x-transition>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 text-center"
                @click.away="showDeleteModal = false">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Delete Confirmation</h3>
                <p class="text-gray-600 mb-4">Are you sure you want to delete this data?</p>
                <div class="bg-gray-100 rounded-lg p-3 mb-4">
                    <p class="font-medium text-gray-800" x-text="deleteItem?.idpel"></p>
                    <p class="text-sm text-gray-500" x-text="deleteItem?.nama"></p>
                </div>
                <p class="text-sm text-red-500 mb-6 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    This action cannot be undone.
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="showDeleteModal = false" class="px-6 py-2 rounded-lg"
                        style="border: 1px solid #C8BFBF;">Cancel</button>
                    <button @click="deleteData()" class="px-6 py-2 bg-red-500 text-white rounded-lg">Yes, Delete</button>
                </div>
            </div>
        </div>

        <!-- Import Result Modal -->
        <div x-show="showImportResultModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            x-transition>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 text-center"
                @click.away="showImportResultModal = false">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"
                    :class="importResult.duplicates > 0 ? 'bg-yellow-100' : 'bg-green-100'">
                    <svg x-show="importResult.duplicates > 0" class="w-8 h-8 text-yellow-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <svg x-show="importResult.duplicates === 0" class="w-8 h-8 text-green-500" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Import Complete</h3>

                <div class="bg-gray-50 rounded-lg p-4 mb-4 text-left space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Successfully imported:</span>
                        <span class="font-bold text-green-600" x-text="importResult.imported"></span>
                    </div>
                    <div x-show="importResult.duplicates > 0" class="flex justify-between">
                        <span class="text-gray-600">Skipped (duplicates):</span>
                        <span class="font-bold text-yellow-600" x-text="importResult.duplicates"></span>
                    </div>
                    <div x-show="importResult.errors > 0" class="flex justify-between">
                        <span class="text-gray-600">Errors:</span>
                        <span class="font-bold text-red-600" x-text="importResult.errors"></span>
                    </div>
                </div>

                <p x-show="importResult.duplicates > 0" class="text-sm text-yellow-600 mb-4">
                    <span x-text="importResult.duplicates"></span> record(s) could not be imported because they already
                    exist in the database (duplicate IDPEL).
                </p>

                <button @click="showImportResultModal = false"
                    class="px-8 py-2.5 bg-[#29AAE1] text-white rounded-lg font-medium hover:bg-[#1E8CC0]">
                    OK
                </button>
            </div>
        </div>

        <!-- Import Progress Modal -->
        <div x-show="isImporting" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-[60]">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center">
                <div class="w-20 h-20 mx-auto mb-6 relative">
                    <!-- Spinning loader -->
                    <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-[#29AAE1] rounded-full animate-spin border-t-transparent">
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-8 h-8 text-[#29AAE1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Importing Data...</h3>
                <p class="text-gray-500 mb-4" x-text="importStatus || 'Processing your file, please wait...'"></p>
                <div class="bg-gray-100 rounded-lg p-3">
                    <p class="text-sm text-gray-600">This may take a few minutes for large files.</p>
                    <p class="text-sm text-gray-500 mt-1">Please do not close or refresh this page.</p>
                </div>
            </div>
        </div>

        <!-- View-Only Modal (Read-only for all users) -->
        <div x-show="showViewModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
            x-transition>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto"
                @click.away="showViewModal = false">
                <div class="flex items-center justify-between p-6" style="border-bottom: 1px solid #C8BFBF;">
                    <div class="flex items-center gap-3">
                        <h3 class="text-xl font-bold text-gray-800">View PJU Data</h3>
                        <span x-show="viewingItem?.is_idpel_main"
                            class="px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            IDPEL MAIN
                        </span>
                    </div>
                    <button @click="showViewModal = false"
                        class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="space-y-3">
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">IDPEL</span>
                                <p class="text-gray-800 font-medium" x-text="viewingItem?.idpel || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NAMA</span>
                                <p class="text-gray-800" x-text="viewingItem?.nama || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NAMAPNJ</span>
                                <p class="text-gray-800" x-text="viewingItem?.namapnj || '-'"></p>
                            </div>
                            <div class="flex gap-4">
                                <div>
                                    <span class="text-xs font-medium text-gray-500 uppercase">RT</span>
                                    <p class="text-gray-800" x-text="viewingItem?.rt || '-'"></p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500 uppercase">RW</span>
                                    <p class="text-gray-800" x-text="viewingItem?.rw || '-'"></p>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">TARIF</span>
                                <p class="text-gray-800" x-text="viewingItem?.tarif || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">DAYA</span>
                                <p class="text-gray-800" x-text="viewingItem?.daya || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">JENISLAYANAN</span>
                                <p class="text-gray-800"
                                    x-text="viewingItem?.jenis_layanan || viewingItem?.jenislayanan || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NOMOR_METER_KWH</span>
                                <p class="text-gray-800 font-mono" x-text="viewingItem?.nomor_meter_kwh || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NOMOR_GARDU</span>
                                <p class="text-gray-800 font-mono" x-text="viewingItem?.nomor_gardu || '-'"></p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NOMOR_JURUSAN_TIANG</span>
                                <p class="text-gray-800 font-mono" x-text="viewingItem?.nomor_jurusan_tiang || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NAMA_GARDU</span>
                                <p class="text-gray-800" x-text="viewingItem?.nama_gardu || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NOMOR_METER_PREPAID</span>
                                <p class="text-gray-800 font-mono" x-text="viewingItem?.nomor_meter_prepaid || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">KOORDINAT_X</span>
                                <p class="text-gray-800 font-mono text-sm" x-text="viewingItem?.koordinat_x || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">KOORDINAT_Y</span>
                                <p class="text-gray-800 font-mono text-sm" x-text="viewingItem?.koordinat_y || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">KDAM</span>
                                <p class="text-gray-800">
                                    <span x-show="viewingItem?.kdam === 'M'"
                                        class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">M
                                        (Meterisasi)</span>
                                    <span x-show="viewingItem?.kdam === 'A'"
                                        class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">A
                                        (Abonemen)</span>
                                    <span
                                        x-show="!viewingItem?.kdam || (viewingItem?.kdam !== 'M' && viewingItem?.kdam !== 'A')"
                                        class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Unclear</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NAMA_KABUPATEN</span>
                                <p class="text-gray-800" x-text="viewingItem?.nama_kabupaten || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NAMA_KECAMATAN</span>
                                <p class="text-gray-800" x-text="viewingItem?.nama_kecamatan || '-'"></p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500 uppercase">NAMA_KELURAHAN</span>
                                <p class="text-gray-800" x-text="viewingItem?.nama_kelurahan || '-'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Photo Section -->
                    <div x-show="viewingItem?.photo" class="border-t pt-4">
                        <span class="text-xs font-medium text-gray-500 uppercase block mb-2">PJU DOCUMENTATION PHOTO</span>
                        <img :src="viewingItem?.photo ? '/storage/' + viewingItem.photo : ''"
                            class="max-w-full max-h-64 rounded-lg shadow-md object-contain">
                    </div>
                    <div x-show="!viewingItem?.photo" class="border-t pt-4">
                        <span class="text-xs font-medium text-gray-500 uppercase block mb-2">PJU DOCUMENTATION PHOTO</span>
                        <div class="w-full h-32 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="ml-2">No photo uploaded</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 p-6" style="border-top: 1px solid #C8BFBF;">
                    <button @click="showViewModal = false"
                        class="px-6 py-2 bg-[#29AAE1] text-white rounded-lg">Close</button>
                </div>
            </div>
        </div>

        <!-- Photo Upload Modal (Employee Only) -->
        <div x-show="showPhotoUploadModal" x-cloak
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" x-transition>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.away="showPhotoUploadModal = false">
                <div class="flex items-center justify-between p-5" style="border-bottom: 1px solid #C8BFBF;">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Upload Photo</h3>
                        <p class="text-sm text-gray-500" x-text="'IDPEL: ' + (photoUploadItem?.idpel || '')"></p>
                    </div>
                    <button @click="showPhotoUploadModal = false"
                        class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                <div class="p-5">
                    <!-- Photo Upload Area -->
                    <div class="border-2 border-dashed rounded-xl p-6 text-center transition-colors"
                        :class="isDragging ? 'border-[#29AAE1] bg-blue-50' : 'border-[#C8BFBF]'"
                        @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop($event)">
                        <template x-if="photoPreview">
                            <div class="flex flex-col items-center gap-3">
                                <img :src="photoPreview" class="w-32 h-32 object-cover rounded-lg">
                                <p class="text-sm text-gray-600" x-text="photoName || 'Current photo'"></p>
                                <button type="button" @click="removePhoto()"
                                    class="text-red-500 text-sm hover:underline">Remove</button>
                            </div>
                        </template>
                        <template x-if="!photoPreview">
                            <div>
                                <svg class="w-10 h-10 mx-auto text-[#29AAE1] mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p class="text-gray-600 text-sm mb-1">Drag and drop or click to upload</p>
                                <p class="text-gray-400 text-xs">JPG, PNG (Max 20MB)</p>
                                <input type="file" accept="image/jpeg,image/png" @change="handleFileSelect($event)"
                                    class="hidden" x-ref="photoFileInput">
                                <button type="button" @click="$refs.photoFileInput.click()"
                                    class="mt-3 px-4 py-2 bg-[#29AAE1] text-white rounded-lg text-sm">Choose File</button>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex justify-end gap-3 p-5" style="border-top: 1px solid #C8BFBF;">
                    <button @click="showPhotoUploadModal = false" class="px-5 py-2 rounded-lg"
                        style="border: 1px solid #C8BFBF;">Cancel</button>
                    <button @click="savePhotoOnly()" class="px-5 py-2 bg-[#29AAE1] text-white rounded-lg">Upload
                        Photo</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
        <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
        <script>
            function pjuReport() {
                return {
                    activeDropdown: null, searchRegional: '', searchStatus: '', searchIdpel: '', searchTable: '',
                    selectedRegionals: [], selectedStatuses: [], selectedIdpels: [],
                    regionals: ['Kab. Cirebon', 'Kota Cirebon', 'Kab. Kuningan', 'Majalengka', 'Indramayu'],
                    statuses: ['M', 'A', 'Unclear'], idpels: [], pjuData: [],
                    currentPage: 1, perPage: 10,
                    showModal: false, isEditing: false, showDeleteModal: false, deleteItem: null,
                    showViewModal: false, viewingItem: null,
                    showPhotoUploadModal: false, photoUploadItem: null,
                    showImportResultModal: false, importResult: { imported: 0, duplicates: 0, errors: 0 },
                    isImporting: false, importStatus: '',
                    isDragging: false, photoPreview: null, photoName: '', photoFile: null,
                    toast: { show: false, message: '', type: 'success' },
                    currentSearch: '', // Persist search after edit
                    form: { idpel: '', nama: '', namapnj: '', rt: '', rw: '', tarif: '', daya: '', jenislayanan: '', nomor_meter_kwh: '', nomor_gardu: '', nomor_jurusan_tiang: '', nama_gardu: '', nomor_meter_prepaid: '', koordinat_x: '', koordinat_y: '', kdam: '', nama_kabupaten: '', nama_kecamatan: '', nama_kelurahan: '', is_idpel_main: false },

                    // Check if another entry with same IDPEL already has is_idpel_main=true
                    hasExistingIdpelMain() {
                        if (!this.form.idpel) return false;
                        const currentId = this.isEditing ? this.editingId : null;
                        return this.pjuData.some(item =>
                            item.idpel === this.form.idpel &&
                            item.is_idpel_main &&
                            item.id !== currentId
                        );
                    },

                    get isAllSelected() { return !this.selectedRegionals.length && !this.selectedStatuses.length && !this.selectedIdpels.length; },
                    get hasActiveFilters() { return this.selectedRegionals.length || this.selectedStatuses.length || this.selectedIdpels.length; },
                    get filteredRegionals() { return this.searchRegional ? this.regionals.filter(r => r.toLowerCase().includes(this.searchRegional.toLowerCase())) : this.regionals; },
                    get filteredStatuses() { return this.searchStatus ? this.statuses.filter(s => s.toLowerCase().includes(this.searchStatus.toLowerCase())) : this.statuses; },
                    get filteredIdpels() { return this.searchIdpel ? this.idpels.filter(i => i.includes(this.searchIdpel)) : this.idpels; },
                    get filteredData() {
                        let data = this.pjuData;
                        if (this.searchTable) data = data.filter(item => item.idpel?.includes(this.searchTable) || item.nama?.toLowerCase().includes(this.searchTable.toLowerCase()));
                        if (this.selectedRegionals.length) data = data.filter(item => this.selectedRegionals.some(r => item.nama_kabupaten?.includes(r.toUpperCase().replace('KAB. ', '').replace('KOTA ', ''))));
                        if (this.selectedStatuses.length) data = data.filter(item => this.selectedStatuses.includes(item.kdam || 'Unclear'));
                        if (this.selectedIdpels.length) data = data.filter(item => this.selectedIdpels.includes(item.idpel));
                        return data.slice((this.currentPage - 1) * this.perPage, this.currentPage * this.perPage);
                    },
                    get totalPages() { return Math.ceil(this.pjuData.length / this.perPage) || 1; },
                    get visiblePages() { const p = []; for (let i = 1; i <= Math.min(3, this.totalPages); i++) p.push(i); return p; },
                    init() { this.loadData(); },
                    async loadData(search = '') {
                        try {
                            let url = '/api/pju-report/data?limit=5000';
                            if (search) {
                                url = `/api/pju-report/data?limit=5000&search=${encodeURIComponent(search)}`;
                            }
                            const res = await fetch(url);
                            const json = await res.json();
                            this.pjuData = json.data || [];
                            this.idpels = [...new Set(this.pjuData.map(d => d.idpel).filter(Boolean))];
                            if (search && this.pjuData.length > 0) {
                                this.showToast(`Found ${this.pjuData.length} results for "${search}"`, 'success');
                            } else if (search && this.pjuData.length === 0) {
                                this.showToast(`No results found for "${search}"`, 'error');
                            }
                        } catch (e) { console.error(e); }
                    },
                    searchServer() {
                        if (this.searchTable.length >= 3) {
                            this.currentSearch = this.searchTable;
                            this.loadData(this.searchTable);
                        } else if (this.searchTable.length === 0) {
                            this.currentSearch = '';
                            this.loadData();
                        }
                    },
                    toggleDropdown(name) { this.activeDropdown = this.activeDropdown === name ? null : name; },
                    selectAll() { this.selectedRegionals = []; this.selectedStatuses = []; this.selectedIdpels = []; },
                    toggleAllRegionals() { this.selectedRegionals = this.selectedRegionals.length === this.regionals.length ? [] : [...this.regionals]; },
                    toggleAllStatuses() { this.selectedStatuses = this.selectedStatuses.length === this.statuses.length ? [] : [...this.statuses]; },
                    toggleAllIdpels() { this.selectedIdpels = this.selectedIdpels.length === this.idpels.length ? [] : [...this.idpels]; },
                    clearFilter(type) { if (type === 'regional') this.selectedRegionals = []; if (type === 'status') this.selectedStatuses = []; if (type === 'idpel') this.selectedIdpels = []; },
                    clearAllFilters() { this.selectAll(); },
                    applyFilter() { this.currentPage = 1; },
                    copyAsNew(item) {
                        // Copy all data except id, coordinates, and photo
                        this.isEditing = false;
                        this.form = { ...item };
                        delete this.form.id;
                        this.form.koordinat_x = '';  // Clear for new location
                        this.form.koordinat_y = '';  // Clear for new location
                        this.photoPreview = null;    // Clear photo
                        this.photoFile = null;
                        this.photoName = '';
                        this.showModal = true;
                        this.showToast('Data copied! Fill new coordinates and photo, then save.', 'success');
                    },
                    openAddModal() { this.isEditing = false; this.resetForm(); this.showModal = true; },
                    openEditModal(item) { this.isEditing = true; this.form = { ...item }; this.editingId = item.id; if (item.photo) { this.photoPreview = '/storage/' + item.photo; } this.showModal = true; },
                    openDeleteModal(item) { this.deleteItem = item; this.showDeleteModal = true; },
                    viewItem(item) { this.viewingItem = item; this.showViewModal = true; },
                    resetForm() { Object.keys(this.form).forEach(k => this.form[k] = ''); this.photoPreview = null; this.photoFile = null; this.photoName = ''; },
                    handleFileSelect(e) { const file = e.target.files[0]; if (file) this.processFile(file); },
                    handleDrop(e) { this.isDragging = false; const file = e.dataTransfer.files[0]; if (file) this.processFile(file); },
                    processFile(file) {
                        if (!['image/jpeg', 'image/png'].includes(file.type)) { this.showToast('Only JPG and PNG files are allowed', 'error'); return; }
                        if (file.size > 20 * 1024 * 1024) { this.showToast('File size must be less than 20MB', 'error'); return; }
                        this.photoFile = file; this.photoName = file.name;
                        const reader = new FileReader(); reader.onload = e => this.photoPreview = e.target.result; reader.readAsDataURL(file);

                        // Extract GPS coordinates from EXIF data
                        this.extractGPSFromPhoto(file);
                    },

                    // Extract GPS coordinates from photo EXIF data
                    extractGPSFromPhoto(file) {
                        const self = this;
                        EXIF.getData(file, function () {
                            const lat = EXIF.getTag(this, 'GPSLatitude');
                            const latRef = EXIF.getTag(this, 'GPSLatitudeRef');
                            const lng = EXIF.getTag(this, 'GPSLongitude');
                            const lngRef = EXIF.getTag(this, 'GPSLongitudeRef');

                            if (lat && lng) {
                                // Convert DMS (degrees, minutes, seconds) to decimal
                                let latitude = lat[0] + lat[1] / 60 + lat[2] / 3600;
                                let longitude = lng[0] + lng[1] / 60 + lng[2] / 3600;

                                // Apply reference (N/S for lat, E/W for lng)
                                if (latRef === 'S') latitude = -latitude;
                                if (lngRef === 'W') longitude = -longitude;

                                // Auto-fill coordinates (koordinat_x = latitude, koordinat_y = longitude)
                                self.form.koordinat_x = latitude.toFixed(8);
                                self.form.koordinat_y = longitude.toFixed(8);

                                self.showToast('📍 GPS coordinates extracted from photo!', 'success');
                            } else {
                                console.log('No GPS data found in photo');
                            }
                        });
                    },
                    removePhoto() { this.photoPreview = null; this.photoFile = null; this.photoName = ''; },
                    async saveData() {
                        const formData = new FormData();
                        Object.keys(this.form).forEach(k => {
                            // Skip is_idpel_main here, handle separately
                            if (k === 'is_idpel_main') return;
                            if (this.form[k]) formData.append(k, this.form[k]);
                        });
                        // Handle is_idpel_main as 1/0 for Laravel
                        formData.append('is_idpel_main', this.form.is_idpel_main ? '1' : '0');
                        if (this.photoFile) formData.append('photo', this.photoFile);
                        try {
                            const url = this.isEditing ? `/api/pju-report/${this.editingId}` : '/api/pju-report';
                            const method = this.isEditing ? 'PUT' : 'POST';
                            if (this.isEditing) formData.append('_method', 'PUT');
                            const res = await fetch(url, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                            const json = await res.json();
                            if (json.success) {
                                this.showToast(json.message, 'success');
                                this.showModal = false;
                                // Persist search after edit
                                this.loadData(this.currentSearch);
                            }
                            else { this.showToast(json.message || 'Error saving data', 'error'); }
                        } catch (e) { console.error(e); this.showToast('Error saving data', 'error'); }
                    },
                    async deleteData() {
                        try {
                            const res = await fetch(`/api/pju-report/${this.deleteItem.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                            const json = await res.json();
                            if (json.success) { this.showToast('Data successfully deleted', 'success'); this.showDeleteModal = false; this.loadData(); }
                        } catch (e) { this.showToast('Error deleting data', 'error'); }
                    },
                    showToast(message, type = 'success') { this.toast = { show: true, message, type }; setTimeout(() => this.toast.show = false, 3000); },

                    // Photo Upload Modal for Employee
                    openPhotoUploadModal(item) {
                        this.photoUploadItem = item;
                        this.photoPreview = item.photo ? '/storage/' + item.photo : null;
                        this.photoFile = null;
                        this.photoName = '';
                        this.showPhotoUploadModal = true;
                    },
                    async savePhotoOnly() {
                        if (!this.photoFile) {
                            this.showToast('Please select a photo to upload', 'error');
                            return;
                        }
                        const formData = new FormData();
                        formData.append('photo', this.photoFile);
                        formData.append('_method', 'PUT');
                        try {
                            const res = await fetch(`/api/pju-report/${this.photoUploadItem.id}/photo`, {
                                method: 'POST',
                                body: formData,
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                            });
                            const json = await res.json();
                            if (json.success) {
                                this.showToast('Photo uploaded successfully', 'success');
                                this.showPhotoUploadModal = false;
                                this.loadData();
                            } else {
                                this.showToast(json.message || 'Error uploading photo', 'error');
                            }
                        } catch (e) { this.showToast('Error uploading photo', 'error'); }
                    },

                    // Import CSV/Excel
                    async handleImport(event, format) {
                        const file = event.target.files[0];
                        if (!file) return;

                        if (format !== 'csv') {
                            this.showToast('Please use CSV format for import.', 'error');
                            event.target.value = '';
                            return;
                        }

                        this.isImporting = true;
                        this.importStatus = `Uploading ${file.name}...`;

                        const formData = new FormData();
                        formData.append('file', file);

                        try {
                            this.importStatus = `Processing ${file.name}... This may take several minutes for large files.`;

                            const res = await fetch('/api/pju-report/import', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                }
                            });

                            const result = await res.json();

                            this.importResult = {
                                imported: result.imported || 0,
                                duplicates: result.duplicates || 0,
                                errors: result.errors || 0,
                                processed: result.processed || 0
                            };

                            // Show result modal if there are duplicates or the import succeeded
                            if (result.duplicates > 0 || result.imported > 0) {
                                this.showImportResultModal = true;
                            }

                            // Reload data after import
                            if (result.imported > 0) {
                                await this.loadData();
                            }

                        } catch (e) {
                            console.error('Import error:', e);
                            this.showToast('Import failed. Please check your file format.', 'error');
                        } finally {
                            this.isImporting = false;
                            event.target.value = '';
                        }
                    },

                    // Export to Excel/CSV
                    exportData(format = 'csv') {
                        if (!this.filteredData.length) {
                            this.showToast('No data to export', 'error');
                            return;
                        }

                        const headers = ['IDPEL', 'NAMA', 'NAMAPNJ', 'RT', 'RW', 'TARIF', 'DAYA', 'JENISLAYANAN',
                            'NOMOR_METER_KWH', 'NOMOR_GARDU', 'NOMOR_JURUSAN_TIANG', 'NAMA_GARDU',
                            'NOMOR_METER_PREPAID', 'KOORDINAT_X', 'KOORDINAT_Y', 'KDAM',
                            'NAMA_KABUPATEN', 'NAMA_KECAMATAN', 'NAMA_KELURAHAN'];

                        const rows = this.filteredData.map(item => [
                            item.idpel || '',
                            item.nama || '',
                            item.namapnj || '',
                            item.rt || '',
                            item.rw || '',
                            item.tarif || '',
                            item.daya || '',
                            item.jenislayanan || item.jenis_layanan || '',
                            item.nomor_meter_kwh || '',
                            item.nomor_gardu || '',
                            item.nomor_jurusan_tiang || '',
                            item.nama_gardu || '',
                            item.nomor_meter_prepaid || '',
                            item.koordinat_x || '',
                            item.koordinat_y || '',
                            item.kdam || '',
                            item.nama_kabupaten || '',
                            item.nama_kecamatan || '',
                            item.nama_kelurahan || ''
                        ]);

                        if (format === 'excel' && typeof XLSX !== 'undefined') {
                            // Export as Excel using SheetJS
                            const ws = XLSX.utils.aoa_to_sheet([headers, ...rows]);
                            const wb = XLSX.utils.book_new();
                            XLSX.utils.book_append_sheet(wb, ws, 'PJU Report');
                            XLSX.writeFile(wb, `pju-report-${new Date().toISOString().slice(0, 10)}.xlsx`);
                        } else {
                            // Export as CSV
                            let csv = headers.join(',') + '\n';
                            rows.forEach(row => {
                                csv += row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',') + '\n';
                            });

                            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                            const url = URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.download = `pju-report-${new Date().toISOString().slice(0, 10)}.csv`;
                            link.click();
                            URL.revokeObjectURL(url);
                        }

                        this.showToast(`Exported ${this.filteredData.length} records as ${format.toUpperCase()}`, 'success');
                    }
                };
            }
        </script>
    @endpush
@endsection