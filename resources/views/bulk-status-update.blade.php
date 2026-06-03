@extends('layouts.app')

@section('title', 'Bulk Status Update')
@section('page-title', 'Bulk Status Update')

@section('content')
    <div x-data="bulkStatusUpdate()" class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Bulk Status Update</h1>
                <p class="text-gray-600 text-sm mt-1">Update KDAM status for multiple PJU records at once</p>
            </div>
            <a href="{{ route('pju-report') }}"
                class="px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Back to Report
            </a>
        </div>

        <!-- Guide / Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
            <h3 class="font-semibold text-blue-800 mb-3 text-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                How to Use
            </h3>
            <div class="space-y-3 text-sm text-blue-900">
                <div>
                    <p class="font-medium mb-1">Requirements:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-800 ml-2">
                        <li>File format: <strong>CSV</strong> or <strong>Excel (.xlsx)</strong></li>
                        <li>File must contain a column named <strong>IDPEL</strong> and a column named <strong>KDAM</strong></li>
                        <li>KDAM values should be <strong>M</strong> (Meterisasi) or <strong>A</strong> (Abodemen)</li>
                        <li>Other columns in the file will be automatically ignored</li>
                    </ul>
                </div>
                <div>
                    <p class="font-medium mb-2">Example file format:</p>
                    <div class="overflow-x-auto">
                        <table class="text-xs border border-blue-300 rounded">
                            <thead>
                                <tr class="bg-blue-100">
                                    <th class="px-4 py-1.5 border-r border-blue-300 text-left">IDPEL</th>
                                    <th class="px-4 py-1.5 border-r border-blue-300 text-left">NAMA</th>
                                    <th class="px-4 py-1.5 border-r border-blue-300 text-left">KDAM</th>
                                    <th class="px-4 py-1.5 text-left text-blue-400">... (ignored)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr>
                                    <td class="px-4 py-1.5 border-r border-blue-200 font-mono">533512496332</td>
                                    <td class="px-4 py-1.5 border-r border-blue-200 text-blue-400">PJU CIHOE 1</td>
                                    <td class="px-4 py-1.5 border-r border-blue-200 font-bold">M</td>
                                    <td class="px-4 py-1.5 text-blue-300">...</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-1.5 border-r border-blue-200 font-mono">533512285169</td>
                                    <td class="px-4 py-1.5 border-r border-blue-200 text-blue-400">PJU CIGOBANG</td>
                                    <td class="px-4 py-1.5 border-r border-blue-200 font-bold">A</td>
                                    <td class="px-4 py-1.5 text-blue-300">...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-blue-600 mt-2">Only the <strong>IDPEL</strong> and <strong>KDAM</strong> columns are required. All other columns are optional and will be ignored.</p>
                </div>
            </div>
        </div>

        <!-- Upload Area -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="drop-zone rounded-xl p-8 text-center cursor-pointer border-2 border-dashed border-gray-300 hover:border-[#29AAE1] hover:bg-blue-50/50 transition"
                :class="{ 'border-[#29AAE1] bg-blue-50': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop($event)"
                @click="$refs.statusFileInput.click()">

                <input type="file" x-ref="statusFileInput" @change="handleFileSelect($event)"
                    accept=".csv,.xlsx,.xls" class="hidden">

                <div class="flex flex-col items-center">
                    <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="text-lg font-semibold text-gray-700 mb-1">
                        Drop your file here
                    </p>
                    <p class="text-gray-500 text-sm">
                        or click to browse - Supports CSV and Excel (.xlsx)
                    </p>
                </div>
            </div>
        </div>

        <!-- Analyzing State -->
        <div x-show="isAnalyzing" class="text-center py-6">
            <div class="inline-flex items-center px-5 py-2.5 bg-[#29AAE1] text-white rounded-lg">
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Analyzing file...
            </div>
        </div>

        <!-- Preview Section -->
        <div x-show="preview && !isAnalyzing" x-cloak class="space-y-4">
            <!-- File Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-800">File Preview</h3>
                    <button @click="clearFile()" class="text-sm text-gray-500 hover:text-red-500 transition">Clear</button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-800" x-text="preview?.total || 0"></p>
                        <p class="text-xs text-gray-500 mt-1">Total Records</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-600" x-text="preview?.will_update || 0"></p>
                        <p class="text-xs text-gray-500 mt-1">Will Update</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-400" x-text="preview?.already_same || 0"></p>
                        <p class="text-xs text-gray-500 mt-1">Already Same (Skip)</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-red-500" x-text="preview?.not_found || 0"></p>
                        <p class="text-xs text-gray-500 mt-1">Not Found</p>
                    </div>
                </div>

                <!-- Not Found Warning -->
                <div x-show="preview?.not_found > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-yellow-800 font-medium mb-1">
                        <span x-text="preview?.not_found"></span> IDPEL(s) not found in the database
                    </p>
                    <p class="text-xs text-yellow-600">These records will be skipped during update. You can review them after processing.</p>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end gap-3">
                    <button @click="clearFile()"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button @click="processUpdate()" :disabled="isProcessing || (preview?.will_update === 0)"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-[#29AAE1] rounded-lg hover:bg-[#1E8CC0] transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Process Update (<span x-text="preview?.will_update || 0"></span> records)</span>
                        <span x-show="isProcessing" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Result Modal -->
        <div x-show="showResultModal" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" x-transition>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.away="showResultModal = false">
                <!-- Header -->
                <div class="p-6 border-b">
                    <div class="w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4"
                        :class="result?.not_found_idpels?.length > 0 ? 'bg-yellow-100' : 'bg-green-100'">
                        <svg x-show="result?.not_found_idpels?.length > 0" class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <svg x-show="!result?.not_found_idpels?.length" class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 text-center">Update Complete</h3>
                </div>

                <!-- Stats -->
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-green-50 rounded-lg p-3">
                            <p class="text-2xl font-bold text-green-600" x-text="result?.updated || 0"></p>
                            <p class="text-xs text-gray-600">Updated</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-2xl font-bold text-gray-400" x-text="result?.skipped || 0"></p>
                            <p class="text-xs text-gray-600">Skipped</p>
                        </div>
                        <div class="bg-red-50 rounded-lg p-3">
                            <p class="text-2xl font-bold text-red-500" x-text="result?.not_found_idpels?.length || 0"></p>
                            <p class="text-xs text-gray-600">Not Found</p>
                        </div>
                    </div>

                    <!-- Not Found IDPELs List -->
                    <div x-show="result?.not_found_idpels?.length > 0" class="border border-red-200 rounded-lg overflow-hidden">
                        <div class="bg-red-50 px-4 py-2 border-b border-red-200">
                            <p class="text-sm font-semibold text-red-800">IDPEL Not Found in Database:</p>
                        </div>
                        <div class="max-h-48 overflow-y-auto p-3">
                            <div class="space-y-1">
                                <template x-for="(idpel, index) in (result?.not_found_idpels || [])" :key="index">
                                    <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded text-sm">
                                        <span class="text-gray-400 text-xs" x-text="(index + 1) + '.'"></span>
                                        <span class="font-mono text-gray-800" x-text="idpel"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-6 border-t flex justify-between items-center">
                    <a x-show="result?.not_found_idpels?.length > 0" href="{{ route('pju-report') }}"
                        class="px-4 py-2 text-sm font-medium text-[#29AAE1] border border-[#29AAE1] rounded-lg hover:bg-blue-50 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add New Data
                    </a>
                    <div x-show="!result?.not_found_idpels?.length"></div>
                    <button @click="showResultModal = false; clearFile()"
                        class="px-6 py-2 text-sm font-medium text-white bg-[#29AAE1] rounded-lg hover:bg-[#1E8CC0] transition">
                        OK
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div x-show="toast.show" x-cloak x-transition
            class="fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white text-sm z-50"
            :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'">
            <span x-text="toast.message"></span>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function bulkStatusUpdate() {
            return {
                isDragging: false,
                isAnalyzing: false,
                isProcessing: false,
                preview: null,
                result: null,
                showResultModal: false,
                fileData: null,
                toast: { show: false, message: '', type: 'success' },

                handleDrop(event) {
                    this.isDragging = false;
                    const files = event.dataTransfer.files;
                    if (files.length > 0) this.analyzeFile(files[0]);
                },

                handleFileSelect(event) {
                    const files = event.target.files;
                    if (files.length > 0) this.analyzeFile(files[0]);
                    // Reset input so same file can be selected again
                    event.target.value = '';
                },

                async analyzeFile(file) {
                    // Validate file type
                    const validTypes = [
                        'text/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ];
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (!['csv', 'xlsx', 'xls'].includes(ext)) {
                        this.showToast('Invalid file type. Please upload a CSV or Excel file.', 'error');
                        return;
                    }

                    this.isAnalyzing = true;
                    this.preview = null;
                    this.result = null;

                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const response = await fetch('/api/bulk-status-update/analyze', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.preview = data.preview;
                            this.fileData = data.file_key;
                        } else {
                            this.showToast(data.message || 'Error analyzing file', 'error');
                        }
                    } catch (error) {
                        console.error('Analyze error:', error);
                        this.showToast('Failed to analyze file. Please try again.', 'error');
                    } finally {
                        this.isAnalyzing = false;
                    }
                },

                async processUpdate() {
                    if (!this.fileData) return;

                    this.isProcessing = true;

                    try {
                        const response = await fetch('/api/bulk-status-update/process', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ file_key: this.fileData })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.result = data;
                            this.showResultModal = true;
                        } else {
                            this.showToast(data.message || 'Error processing update', 'error');
                        }
                    } catch (error) {
                        console.error('Process error:', error);
                        this.showToast('Failed to process update. Please try again.', 'error');
                    } finally {
                        this.isProcessing = false;
                    }
                },

                clearFile() {
                    this.preview = null;
                    this.fileData = null;
                    this.result = null;
                },

                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => this.toast.show = false, 3000);
                }
            };
        }
    </script>
@endpush
