<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bulk Photo Upload - PJU Cirebon</title>
    <link rel="icon" href="{{ asset('pln.png') }}" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .dark {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .dark .bg-white {
            background-color: #1e293b !important;
        }

        .dark .text-gray-900 {
            color: #f1f5f9 !important;
        }

        .dark .text-gray-700 {
            color: #cbd5e1 !important;
        }

        .dark .text-gray-600 {
            color: #94a3b8 !important;
        }

        .dark .text-gray-500 {
            color: #64748b !important;
        }

        .dark .border-gray-200 {
            border-color: #334155 !important;
        }

        .dark .border-gray-300 {
            border-color: #475569 !important;
        }

        .dark .bg-gray-50 {
            background-color: #1e293b !important;
        }

        .dark .bg-gray-100 {
            background-color: #334155 !important;
        }

        .drop-zone {
            border: 3px dashed #29AAE1;
            background: linear-gradient(135deg, rgba(41, 170, 225, 0.05) 0%, rgba(30, 140, 192, 0.05) 100%);
            transition: all 0.3s ease;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: #1E8CC0;
            background: linear-gradient(135deg, rgba(41, 170, 225, 0.15) 0%, rgba(30, 140, 192, 0.15) 100%);
            transform: scale(1.01);
        }

        .status-match {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-duplicate {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-not_found {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .dark .status-match {
            background-color: #064e3b;
            color: #6ee7b7;
        }

        .dark .status-duplicate {
            background-color: #78350f;
            color: #fcd34d;
        }

        .dark .status-not_found {
            background-color: #7f1d1d;
            color: #fca5a5;
        }
    </style>
</head>

<body x-data="photoUpload()" :class="{ 'dark': darkMode }">
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900">

        @include('components.navbar')
        @include('components.sidebar')

        <!-- Main Content -->
        <main class="pt-14 transition-all duration-300" :class="sidebarOpen ? 'ml-[180px]' : 'ml-[60px]'">
            <div class="p-6">

                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bulk Photo Upload</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Upload multiple PJU photos at once with
                            auto-matching</p>
                    </div>
                    <a href="{{ route('pju-report') }}"
                        class="px-4 py-2 text-sm bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
                        ← Back to Report
                    </a>
                </div>

                <!-- Drop Zone -->
                <div class="drop-zone rounded-2xl p-12 text-center cursor-pointer mb-6"
                    :class="{ 'dragover': isDragging }" @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop($event)"
                    @click="$refs.fileInput.click()">

                    <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" multiple accept="image/*"
                        class="hidden">

                    <div class="flex flex-col items-center">
                        <svg class="w-16 h-16 text-[#29AAE1] mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Drag & Drop Photos Here
                        </p>
                        <p class="text-gray-500 dark:text-gray-400">
                            or click to browse • Supports JPG, PNG up to 20MB each
                        </p>
                    </div>
                </div>

                <!-- Filename Format Guide -->
                <div
                    class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
                    <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Supported Filename Formats:
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white dark:bg-slate-800 p-3 rounded-lg">
                            <code class="text-blue-600 dark:text-blue-400">533113714188.jpg</code>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">→ IDPEL Main (attach)</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-3 rounded-lg">
                            <code class="text-amber-600 dark:text-amber-400">533113714188(2).jpg</code>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">→ Branch #2 (auto-duplicate)</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-3 rounded-lg">
                            <code class="text-green-600 dark:text-green-400">533113714188_-6.767,108.556.jpg</code>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">→ With coordinates</p>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="isAnalyzing" class="text-center py-8">
                    <div class="inline-flex items-center px-6 py-3 bg-[#29AAE1] text-white rounded-lg">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Analyzing files...
                    </div>
                </div>

                <!-- Results Table -->
                <div x-show="results.length > 0 && !isAnalyzing"
                    class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Preview Upload (<span x-text="results.length"></span> files)
                        </h3>
                        <div class="flex gap-2">
                            <button @click="clearAll()"
                                class="px-4 py-2 text-sm bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
                                Clear All
                            </button>
                            <button @click="processUploads()" :disabled="isProcessing"
                                class="px-6 py-2 text-sm bg-[#29AAE1] text-white rounded-lg hover:bg-[#1E8CC0] transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!isProcessing">✓ Process All</span>
                                <span x-show="isProcessing">Processing...</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-slate-900">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Preview</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Filename</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        IDPEL</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        Action</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                <template x-for="(item, index) in results" :key="index">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                        <td class="px-4 py-3">
                                            <img :src="'/storage/' + item.temp_path"
                                                class="w-12 h-12 object-cover rounded-lg" alt="">
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900 dark:text-white"
                                                x-text="item.original_name"></span>
                                            <div x-show="item.parsed.sequence"
                                                class="text-xs text-amber-600 dark:text-amber-400">
                                                Branch #<span x-text="item.parsed.sequence"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-mono text-sm text-gray-700 dark:text-gray-300"
                                                x-text="item.parsed.idpel || '-'"></span>
                                            <div x-show="item.parsed.lat" class="text-xs text-gray-500">
                                                <span x-text="item.parsed.lat?.toFixed(4)"></span>, <span
                                                    x-text="item.parsed.lng?.toFixed(4)"></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="{
                                                    'status-match': item.match.status === 'match',
                                                    'status-duplicate': item.match.status === 'duplicate',
                                                    'status-not_found': item.match.status === 'not_found'
                                                }"
                                                x-text="item.match.status === 'match' ? '✓ Match' : item.match.status === 'duplicate' ? '⚡ Duplicate' : '✗ Not Found'">
                                            </span>
                                            <p class="text-xs text-gray-500 mt-1" x-text="item.match.message"></p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <select x-model="item.action"
                                                class="text-sm border rounded-lg px-2 py-1 bg-white dark:bg-slate-700 dark:border-slate-600">
                                                <option value="attach" x-show="item.match.status === 'match'">Attach to
                                                    record</option>
                                                <option value="duplicate" x-show="item.match.status === 'duplicate'">
                                                    Create duplicate</option>
                                                <option value="skip">Skip</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3">
                                            <button @click="removeItem(index)" class="text-red-500 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Process Result -->
                <div x-show="processResult"
                    class="mt-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-green-800 dark:text-green-300 mb-3">Upload Complete!</h3>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-white dark:bg-slate-800 p-4 rounded-lg">
                            <p class="text-3xl font-bold text-green-600" x-text="processResult?.processed || 0"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Photos Attached</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-4 rounded-lg">
                            <p class="text-3xl font-bold text-amber-600" x-text="processResult?.duplicated || 0"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Records Created</p>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-4 rounded-lg">
                            <p class="text-3xl font-bold text-gray-500" x-text="processResult?.skipped || 0"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Skipped</p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function photoUpload() {
            return {
                darkMode: localStorage.getItem('darkMode') === 'true',
                sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
                isDragging: false,
                isAnalyzing: false,
                isProcessing: false,
                results: [],
                processResult: null,

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                },

                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                    localStorage.setItem('sidebarOpen', this.sidebarOpen);
                },

                handleDrop(event) {
                    this.isDragging = false;
                    const files = event.dataTransfer.files;
                    this.uploadFiles(files);
                },

                handleFileSelect(event) {
                    const files = event.target.files;
                    this.uploadFiles(files);
                },

                async uploadFiles(files) {
                    if (files.length === 0) return;

                    this.isAnalyzing = true;
                    this.processResult = null;

                    const formData = new FormData();
                    for (let i = 0; i < files.length; i++) {
                        formData.append('files[]', files[i]);
                    }

                    try {
                        const response = await fetch('/api/photo-upload/analyze', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Set default action for each result
                            data.results.forEach(item => {
                                item.action = item.match.action || 'skip';
                            });
                            this.results = [...this.results, ...data.results];
                        } else {
                            alert('Error analyzing files: ' + (data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Upload error:', error);
                        alert('Failed to upload files. Please try again.');
                    } finally {
                        this.isAnalyzing = false;
                    }
                },

                removeItem(index) {
                    this.results.splice(index, 1);
                },

                clearAll() {
                    this.results = [];
                    this.processResult = null;
                },

                async processUploads() {
                    if (this.results.length === 0) return;

                    this.isProcessing = true;

                    const items = this.results.map(item => ({
                        temp_path: item.temp_path,
                        action: item.action,
                        target_id: item.match.target_id,
                        parsed: item.parsed
                    }));

                    try {
                        const response = await fetch('/api/photo-upload/process', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ items })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.processResult = data;
                            this.results = [];
                        } else {
                            alert('Error processing files: ' + (data.message || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Process error:', error);
                        alert('Failed to process uploads. Please try again.');
                    } finally {
                        this.isProcessing = false;
                    }
                }
            };
        }
    </script>
</body>

</html>