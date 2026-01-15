@extends('layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
    <div x-data="analyticsPage()" x-init="init()" class="space-y-6">
        <!-- Header with Filters and Export -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-medium text-gray-600">Filter:</span>

                    <!-- Wilayah Filter -->
                    <select x-model="filters.wilayah" @change="loadAllCharts()"
                        class="px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1]"
                        style="border-color: #C8BFBF;">
                        <option value="">Semua Wilayah</option>
                        <template x-for="w in filterOptions.wilayah" :key="w">
                            <option :value="w" x-text="w"></option>
                        </template>
                    </select>

                    <!-- Status Filter -->
                    <select x-model="filters.status" @change="loadAllCharts()"
                        class="px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1]"
                        style="border-color: #C8BFBF;">
                        <option value="">Semua Status</option>
                        <option value="M">Meterisasi</option>
                        <option value="A">Abonemen</option>
                        <option value="unclear">Unclear</option>
                    </select>

                    <!-- Daya Filter -->
                    <select x-model="filters.daya" @change="loadAllCharts()"
                        class="px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1]"
                        style="border-color: #C8BFBF;">
                        <option value="">Semua Daya</option>
                        <template x-for="d in filterOptions.daya" :key="d">
                            <option :value="d" x-text="d + ' VA'"></option>
                        </template>
                    </select>

                    <button @click="resetFilters()" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                        Reset
                    </button>
                </div>

                <!-- Export Buttons -->
                <div class="flex items-center gap-2">
                    <button @click="exportPDF()"
                        class="flex items-center gap-2 px-4 py-2 bg-[#EB2027] text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export PDF
                    </button>
                    <button @click="exportExcel()"
                        class="flex items-center gap-2 px-4 py-2 bg-[#17C353] text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div id="charts-container" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Status Meter Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Distribusi Status Meter</h3>
                <div class="relative h-72">
                    <canvas id="statusChart"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-gray-800" x-text="statusData.total?.toLocaleString() || '0'">
                            </p>
                            <p class="text-sm text-gray-500">Total PJU</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center gap-6 mt-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-[#17C353]"></div>
                        <span class="text-sm text-gray-600">Meterisasi</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-[#FBED21]"></div>
                        <span class="text-sm text-gray-600">Abonemen</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-[#EB2027]"></div>
                        <span class="text-sm text-gray-600">Unclear</span>
                    </div>
                </div>
            </div>

            <!-- Wilayah Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">PJU per Wilayah</h3>
                <div class="h-72">
                    <canvas id="wilayahChart"></canvas>
                </div>
            </div>

            <!-- Daya Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Distribusi Daya</h3>
                <div class="h-72">
                    <canvas id="dayaChart"></canvas>
                </div>
            </div>

            <!-- IDPEL Analysis -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Analisis IDPEL (Potensi Anomali)</h3>
                    <span x-show="idpelData.total_anomalies > 0"
                        class="px-3 py-1 text-sm font-medium text-white bg-[#EB2027] rounded-full"
                        x-text="idpelData.total_anomalies + ' Anomali'"></span>
                </div>
                <p class="text-sm text-gray-500 mb-3">IDPEL dengan &gt;3 PJU. <span class="text-[#EB2027]">Merah</span> =
                    Daya ≤900VA (potensi illegal)</p>
                <div class="overflow-y-auto max-h-56">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left text-gray-600">IDPEL</th>
                                <th class="px-3 py-2 text-left text-gray-600">Daya</th>
                                <th class="px-3 py-2 text-left text-gray-600">Jumlah</th>
                                <th class="px-3 py-2 text-left text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in idpelData.data" :key="item.idpel">
                                <tr :class="item.is_anomaly ? 'bg-red-50' : ''">
                                    <td class="px-3 py-2 font-mono"
                                        :class="item.is_anomaly ? 'text-[#EB2027] font-bold' : 'text-gray-800'"
                                        x-text="item.idpel"></td>
                                    <td class="px-3 py-2 text-gray-600" x-text="item.daya + ' VA'"></td>
                                    <td class="px-3 py-2 font-bold"
                                        :class="item.is_anomaly ? 'text-[#EB2027]' : 'text-gray-800'"
                                        x-text="item.pju_count"></td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium"
                                            :class="item.kdam === 'M' ? 'bg-green-100 text-green-700' : item.kdam === 'A' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                                            x-text="item.status"></span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!idpelData.data || idpelData.data.length === 0">
                                <td colspan="4" class="px-3 py-8 text-center text-gray-400">Tidak ada data anomali</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

        <script>
            function analyticsPage() {
                return {
                    filters: { wilayah: '', status: '', daya: '' },
                    filterOptions: { wilayah: [], daya: [], status: [] },
                    statusData: { total: 0 },
                    idpelData: { data: [], total_anomalies: 0 },
                    charts: {},

                    async init() {
                        await this.loadFilterOptions();
                        await this.loadAllCharts();
                    },

                    async loadFilterOptions() {
                        try {
                            const res = await fetch('/api/analytics/filters');
                            this.filterOptions = await res.json();
                        } catch (e) {
                            console.error('Error loading filters:', e);
                        }
                    },

                    async loadAllCharts() {
                        await Promise.all([
                            this.loadStatusChart(),
                            this.loadWilayahChart(),
                            this.loadDayaChart(),
                            this.loadIdpelAnalysis()
                        ]);
                    },

                    buildQuery() {
                        const params = new URLSearchParams();
                        if (this.filters.wilayah) params.append('wilayah', this.filters.wilayah);
                        if (this.filters.status) params.append('status', this.filters.status);
                        if (this.filters.daya) params.append('daya', this.filters.daya);
                        return params.toString() ? '?' + params.toString() : '';
                    },

                    async loadStatusChart() {
                        try {
                            const res = await fetch('/api/analytics/status' + this.buildQuery());
                            const data = await res.json();
                            this.statusData = data;

                            if (this.charts.status) this.charts.status.destroy();

                            const ctx = document.getElementById('statusChart').getContext('2d');
                            this.charts.status = new Chart(ctx, {
                                type: 'doughnut',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        data: data.data,
                                        backgroundColor: data.colors,
                                        borderWidth: 0,
                                        hoverOffset: 10
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    cutout: '65%',
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: (ctx) => `${ctx.label}: ${ctx.raw.toLocaleString()} PJU`
                                            }
                                        }
                                    }
                                }
                            });
                        } catch (e) {
                            console.error('Error loading status chart:', e);
                        }
                    },

                    async loadWilayahChart() {
                        try {
                            const res = await fetch('/api/analytics/wilayah' + this.buildQuery());
                            const data = await res.json();

                            if (this.charts.wilayah) this.charts.wilayah.destroy();

                            const ctx = document.getElementById('wilayahChart').getContext('2d');
                            this.charts.wilayah = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        data: data.data,
                                        backgroundColor: data.colors,
                                        borderRadius: 6,
                                        hoverBackgroundColor: data.colors.map(c => c + 'CC')
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: (ctx) => `${ctx.raw.toLocaleString()} PJU`
                                            }
                                        }
                                    },
                                    scales: {
                                        x: { grid: { display: false } },
                                        y: { grid: { display: false } }
                                    }
                                }
                            });
                        } catch (e) {
                            console.error('Error loading wilayah chart:', e);
                        }
                    },

                    async loadDayaChart() {
                        try {
                            const res = await fetch('/api/analytics/daya' + this.buildQuery());
                            const data = await res.json();

                            if (this.charts.daya) this.charts.daya.destroy();

                            const ctx = document.getElementById('dayaChart').getContext('2d');
                            this.charts.daya = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        data: data.data,
                                        backgroundColor: '#29AAE1',
                                        borderRadius: 6,
                                        hoverBackgroundColor: '#1E8CC0'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: {
                                            callbacks: {
                                                label: (ctx) => `${ctx.raw.toLocaleString()} PJU`
                                            }
                                        }
                                    },
                                    scales: {
                                        x: { grid: { display: false } },
                                        y: { grid: { color: '#f0f0f0' }, beginAtZero: true }
                                    }
                                }
                            });
                        } catch (e) {
                            console.error('Error loading daya chart:', e);
                        }
                    },

                    async loadIdpelAnalysis() {
                        try {
                            const res = await fetch('/api/analytics/idpel' + this.buildQuery());
                            this.idpelData = await res.json();
                        } catch (e) {
                            console.error('Error loading IDPEL analysis:', e);
                        }
                    },

                    resetFilters() {
                        this.filters = { wilayah: '', status: '', daya: '' };
                        this.loadAllCharts();
                    },

                    exportPDF() {
                        const element = document.getElementById('charts-container');
                        const opt = {
                            margin: 10,
                            filename: 'analytics-pju-' + new Date().toISOString().slice(0, 10) + '.pdf',
                            image: { type: 'jpeg', quality: 0.98 },
                            html2canvas: { scale: 2 },
                            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                        };
                        html2pdf().set(opt).from(element).save();
                    },

                    exportExcel() {
                        // Prepare data for Excel
                        const wb = XLSX.utils.book_new();

                        // Status sheet
                        const statusSheet = XLSX.utils.aoa_to_sheet([
                            ['Status', 'Jumlah'],
                            ['Meterisasi', this.statusData.data?.[0] || 0],
                            ['Abonemen', this.statusData.data?.[1] || 0],
                            ['Unclear', this.statusData.data?.[2] || 0],
                            ['Total', this.statusData.total || 0]
                        ]);
                        XLSX.utils.book_append_sheet(wb, statusSheet, 'Status Meter');

                        // IDPEL Anomaly sheet
                        if (this.idpelData.data?.length) {
                            const idpelRows = [['IDPEL', 'Daya', 'Jumlah PJU', 'Status', 'Anomali']];
                            this.idpelData.data.forEach(item => {
                                idpelRows.push([item.idpel, item.daya, item.pju_count, item.status, item.is_anomaly ? 'Ya' : 'Tidak']);
                            });
                            const idpelSheet = XLSX.utils.aoa_to_sheet(idpelRows);
                            XLSX.utils.book_append_sheet(wb, idpelSheet, 'Analisis IDPEL');
                        }

                        XLSX.writeFile(wb, 'analytics-pju-' + new Date().toISOString().slice(0, 10) + '.xlsx');
                    }
                };
            }
        </script>
    @endpush
@endsection