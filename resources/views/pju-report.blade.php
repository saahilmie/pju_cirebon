@extends('layouts.app')

@section('title', 'PJU Report')
@section('page-title', 'PJU Report')

@section('content')
    <div x-data="pjuReport()" x-init="init()" class="p-6">
        <!-- PJU Report Header -->
        <h2 class="text-xl font-bold text-gray-800 mb-4">PJU Report</h2>

        <!-- Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm border p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- All Button -->
                    <button @click="selectAll()" class="px-5 py-2 rounded-full text-sm font-medium transition-all"
                        :class="isAllSelected ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                        All
                    </button>

                    <!-- Regional Filter -->
                    <div class="relative">
                        <button @click="toggleDropdown('regional')"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-2"
                            :class="selectedRegionals.length > 0 ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            Regional
                            <span x-show="selectedRegionals.length > 0"
                                class="bg-white text-[#29AAE1] text-xs font-bold px-1.5 py-0.5 rounded-full"
                                x-text="selectedRegionals.length"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            <button x-show="selectedRegionals.length > 0" @click.stop="clearFilter('regional')"
                                class="ml-1 hover:text-red-200">×</button>
                        </button>

                        <!-- Regional Dropdown -->
                        <div x-show="activeDropdown === 'regional'" @click.away="activeDropdown = null" x-transition
                            class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-xl border z-50 w-64">
                            <div class="p-3 border-b">
                                <div class="relative">
                                    <input type="text" x-model="searchRegional" placeholder="Search..."
                                        class="w-full pl-3 pr-10 py-2 border rounded-lg text-sm focus:outline-none focus:border-[#29AAE1]">
                                    <svg class="w-5 h-5 text-[#29AAE1] absolute right-3 top-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="max-h-48 overflow-y-auto p-2">
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" :checked="selectedRegionals.length === regionals.length"
                                        @change="toggleAllRegionals()"
                                        class="w-4 h-4 rounded border-gray-300 text-[#29AAE1]">
                                    <span class="font-medium">All Regionals</span>
                                    <span class="text-gray-400 text-sm ml-auto">Regionals(<span
                                            x-text="regionals.length"></span>)</span>
                                </label>
                                <template x-for="region in filteredRegionals" :key="region">
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer pl-6">
                                        <input type="checkbox" :value="region" x-model="selectedRegionals"
                                            class="w-4 h-4 rounded border-gray-300 text-[#29AAE1]">
                                        <span x-text="region" class="text-sm"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="p-3 border-t">
                                <button @click="applyFilter(); activeDropdown = null"
                                    class="w-full bg-[#29AAE1] text-white py-2 rounded-lg font-medium hover:bg-[#1E8CC0]">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <button @click="toggleDropdown('status')"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-2"
                            :class="selectedStatuses.length > 0 ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            Status
                            <span x-show="selectedStatuses.length > 0"
                                class="bg-white text-[#29AAE1] text-xs font-bold px-1.5 py-0.5 rounded-full"
                                x-text="selectedStatuses.length"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Status Dropdown -->
                        <div x-show="activeDropdown === 'status'" @click.away="activeDropdown = null" x-transition
                            class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-xl border z-50 w-56">
                            <div class="p-3 border-b">
                                <div class="relative">
                                    <input type="text" x-model="searchStatus" placeholder="Search..."
                                        class="w-full pl-3 pr-10 py-2 border rounded-lg text-sm focus:outline-none focus:border-[#29AAE1]">
                                    <svg class="w-5 h-5 text-[#29AAE1] absolute right-3 top-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="max-h-48 overflow-y-auto p-2">
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" :checked="selectedStatuses.length === statuses.length"
                                        @change="toggleAllStatuses()"
                                        class="w-4 h-4 rounded border-gray-300 text-[#29AAE1]">
                                    <span class="font-medium">All Status</span>
                                    <span class="text-gray-400 text-sm ml-auto">Status(<span
                                            x-text="statuses.length"></span>)</span>
                                </label>
                                <template x-for="status in filteredStatuses" :key="status">
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer pl-6">
                                        <input type="checkbox" :value="status" x-model="selectedStatuses"
                                            class="w-4 h-4 rounded border-gray-300 text-[#29AAE1]">
                                        <span x-text="status" class="text-sm"></span>
                                    </label>
                                </template>
                            </div>
                            <div class="p-3 border-t">
                                <button @click="applyFilter(); activeDropdown = null"
                                    class="w-full bg-[#29AAE1] text-white py-2 rounded-lg font-medium hover:bg-[#1E8CC0]">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- IDPEL Filter -->
                    <div class="relative">
                        <button @click="toggleDropdown('idpel')"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-2"
                            :class="selectedIdpels.length > 0 ? 'bg-[#29AAE1] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            IDPEL
                            <span x-show="selectedIdpels.length > 0"
                                class="bg-white text-[#29AAE1] text-xs font-bold px-1.5 py-0.5 rounded-full"
                                x-text="selectedIdpels.length"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- IDPEL Dropdown -->
                        <div x-show="activeDropdown === 'idpel'" @click.away="activeDropdown = null" x-transition
                            class="absolute top-full left-0 mt-2 bg-white rounded-xl shadow-xl border z-50 w-64">
                            <div class="p-3 border-b">
                                <div class="relative">
                                    <input type="text" x-model="searchIdpel" placeholder="Search..."
                                        class="w-full pl-3 pr-10 py-2 border rounded-lg text-sm focus:outline-none focus:border-[#29AAE1]">
                                    <svg class="w-5 h-5 text-[#29AAE1] absolute right-3 top-2" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="max-h-48 overflow-y-auto p-2">
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" :checked="selectedIdpels.length === idpels.length"
                                        @change="toggleAllIdpels()" class="w-4 h-4 rounded border-gray-300 text-[#29AAE1]">
                                    <span class="font-medium">All IDPELs</span>
                                    <span class="text-gray-400 text-sm ml-auto">IDPELs(<span
                                            x-text="idpels.length"></span>)</span>
                                </label>
                                <template x-for="idpel in filteredIdpels.slice(0, 50)" :key="idpel">
                                    <label
                                        class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer pl-6">
                                        <input type="checkbox" :value="idpel" x-model="selectedIdpels"
                                            class="w-4 h-4 rounded border-gray-300 text-[#29AAE1]">
                                        <span x-text="idpel" class="text-sm font-mono"></span>
                                    </label>
                                </template>
                                <p x-show="filteredIdpels.length > 50" class="text-xs text-gray-400 text-center py-2">
                                    Showing 50 of <span x-text="filteredIdpels.length"></span> results...
                                </p>
                            </div>
                            <div class="p-3 border-t">
                                <button @click="applyFilter(); activeDropdown = null"
                                    class="w-full bg-[#29AAE1] text-white py-2 rounded-lg font-medium hover:bg-[#1E8CC0]">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clear All Button -->
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
        <div class="bg-white rounded-xl shadow-sm border">
            <!-- Report Header -->
            <div class="p-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">All Report</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <input type="text" placeholder="Find ID Pel and Status" x-model="searchTable"
                            class="pl-4 pr-10 py-2 border rounded-lg text-sm w-64 focus:outline-none focus:border-[#29AAE1]">
                        <svg class="w-5 h-5 text-[#29AAE1] absolute right-3 top-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button class="px-4 py-2 border rounded-lg text-sm flex items-center gap-2 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import Excel/CSV
                    </button>
                    <button class="px-4 py-2 border rounded-lg text-sm flex items-center gap-2 hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export Excel/CSV
                    </button>
                    <button
                        class="px-4 py-2 bg-[#29AAE1] text-white rounded-lg text-sm flex items-center gap-2 hover:bg-[#1E8CC0]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Data
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#29AAE1] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold">NO</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">IDPEL</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">NAMA</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">NAMAPNJ</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">RT</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">RW</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">TARIF</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">DAYA</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">JENISLAYANAN</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template x-for="(item, index) in filteredData" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600"
                                    x-text="(currentPage - 1) * perPage + index + 1"></td>
                                <td class="px-4 py-3 text-sm text-gray-800 font-medium" x-text="item.idpel"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.nama"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.namapnj || '-'"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.rt || '000'"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.rw || '00'"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.tarif"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.daya"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.jenis_layanan || 'PRABAYAR'"></td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <button class="text-gray-400 hover:text-[#29AAE1]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button class="text-gray-400 hover:text-yellow-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button class="text-gray-400 hover:text-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredData.length === 0">
                            <td colspan="10" class="px-4 py-8 text-center text-gray-400">No data found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t flex items-center justify-end gap-2">
                <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                    class="px-3 py-1 border rounded text-sm disabled:opacity-50">
                    &lt;
                </button>
                <template x-for="page in visiblePages" :key="page">
                    <button @click="currentPage = page" class="px-3 py-1 border rounded text-sm"
                        :class="currentPage === page ? 'bg-[#29AAE1] text-white' : 'hover:bg-gray-50'"
                        x-text="page"></button>
                </template>
                <span x-show="totalPages > 5" class="text-gray-400">...</span>
                <button x-show="totalPages > 5" @click="currentPage = totalPages" class="px-3 py-1 border rounded text-sm"
                    :class="currentPage === totalPages && 'bg-[#29AAE1] text-white'" x-text="totalPages"></button>
                <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages"
                    class="px-3 py-1 border rounded text-sm disabled:opacity-50">
                    &gt;
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function pjuReport() {
                return {
                    // Filter state
                    activeDropdown: null,
                    searchRegional: '',
                    searchStatus: '',
                    searchIdpel: '',
                    searchTable: '',

                    // Selected filters
                    selectedRegionals: [],
                    selectedStatuses: [],
                    selectedIdpels: [],

                    // Data
                    regionals: ['Kab. Cirebon', 'Kota Cirebon', 'Kab. Kuningan', 'Majalengka', 'Indramayu'],
                    statuses: ['M', 'A', 'Unclear'],
                    idpels: [],
                    pjuData: [],

                    // Pagination
                    currentPage: 1,
                    perPage: 10,

                    get isAllSelected() {
                        return this.selectedRegionals.length === 0 &&
                            this.selectedStatuses.length === 0 &&
                            this.selectedIdpels.length === 0;
                    },

                    get hasActiveFilters() {
                        return this.selectedRegionals.length > 0 ||
                            this.selectedStatuses.length > 0 ||
                            this.selectedIdpels.length > 0;
                    },

                    get filteredRegionals() {
                        if (!this.searchRegional) return this.regionals;
                        return this.regionals.filter(r => r.toLowerCase().includes(this.searchRegional.toLowerCase()));
                    },

                    get filteredStatuses() {
                        if (!this.searchStatus) return this.statuses;
                        return this.statuses.filter(s => s.toLowerCase().includes(this.searchStatus.toLowerCase()));
                    },

                    get filteredIdpels() {
                        if (!this.searchIdpel) return this.idpels;
                        return this.idpels.filter(i => i.includes(this.searchIdpel));
                    },

                    get filteredData() {
                        let data = this.pjuData;

                        // Filter by table search
                        if (this.searchTable) {
                            data = data.filter(item =>
                                item.idpel?.includes(this.searchTable) ||
                                item.nama?.toLowerCase().includes(this.searchTable.toLowerCase())
                            );
                        }

                        // Paginate
                        const start = (this.currentPage - 1) * this.perPage;
                        return data.slice(start, start + this.perPage);
                    },

                    get totalPages() {
                        return Math.ceil(this.pjuData.length / this.perPage);
                    },

                    get visiblePages() {
                        const pages = [];
                        for (let i = 1; i <= Math.min(3, this.totalPages); i++) {
                            pages.push(i);
                        }
                        return pages;
                    },

                    init() {
                        this.loadData();
                    },

                    async loadData() {
                        try {
                            const response = await fetch('/api/pju-data');
                            const data = await response.json();
                            this.pjuData = data.data || data;
                            this.idpels = [...new Set(this.pjuData.map(d => d.idpel).filter(Boolean))];
                        } catch (e) {
                            // Sample data
                            this.pjuData = [
                                { id: 1, idpel: '533310506165', nama: 'LIE** ********', namapnj: '***YA CARAKA B2', rt: '000', rw: '00', tarif: 'R1', daya: '2200', jenis_layanan: 'PASKABAYAR' },
                                { id: 2, idpel: '533113026974', nama: 'A.Y* ******* *', namapnj: '***YA CARAKA C1', rt: '002', rw: '05', tarif: 'P3', daya: '1300', jenis_layanan: 'PRABAYAR' },
                            ];
                            this.idpels = this.pjuData.map(d => d.idpel);
                        }
                    },

                    toggleDropdown(name) {
                        this.activeDropdown = this.activeDropdown === name ? null : name;
                    },

                    selectAll() {
                        this.selectedRegionals = [];
                        this.selectedStatuses = [];
                        this.selectedIdpels = [];
                        this.applyFilter();
                    },

                    toggleAllRegionals() {
                        if (this.selectedRegionals.length === this.regionals.length) {
                            this.selectedRegionals = [];
                        } else {
                            this.selectedRegionals = [...this.regionals];
                        }
                    },

                    toggleAllStatuses() {
                        if (this.selectedStatuses.length === this.statuses.length) {
                            this.selectedStatuses = [];
                        } else {
                            this.selectedStatuses = [...this.statuses];
                        }
                    },

                    toggleAllIdpels() {
                        if (this.selectedIdpels.length === this.idpels.length) {
                            this.selectedIdpels = [];
                        } else {
                            this.selectedIdpels = [...this.idpels];
                        }
                    },

                    clearFilter(type) {
                        if (type === 'regional') this.selectedRegionals = [];
                        if (type === 'status') this.selectedStatuses = [];
                        if (type === 'idpel') this.selectedIdpels = [];
                        this.applyFilter();
                    },

                    clearAllFilters() {
                        this.selectedRegionals = [];
                        this.selectedStatuses = [];
                        this.selectedIdpels = [];
                        this.applyFilter();
                    },

                    applyFilter() {
                        this.currentPage = 1;
                        // Filter logic can be expanded here
                    }
                };
            }
        </script>
    @endpush
@endsection