@extends('layouts.app')

@section('title', 'Photo Upload')
@section('page-title', 'Photo Upload')

@section('content')
    <div x-data="photoUpload()" class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Bulk Photo Upload</h1>
                <p class="text-gray-600 text-sm mt-1">Upload multiple PJU photos at once with
                    auto-matching</p>
            </div>
            <a href="{{ route('pju-report') }}"
                class="px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300:bg-slate-600 transition">
                Back to Report
            </a>
        </div>

        <!-- Drop Zone -->
        <div class="drop-zone rounded-xl p-8 text-center cursor-pointer border-2 border-dashed border-[#29AAE1] bg-blue-50/50 hover:bg-blue-50 transition"
            :class="{ 'bg-blue-100': isDragging }" @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()">

            <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" multiple accept="image/*"
                class="hidden">

            <div class="flex flex-col items-center">
                <svg class="w-12 h-12 text-[#29AAE1] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-lg font-semibold text-gray-700 mb-1">
                    Drag & Drop Photos Here
                </p>
                <p class="text-gray-500 text-sm">
                    or click to browse - Supports JPG, PNG up to 20MB each
                </p>
            </div>
        </div>

        <!-- Filename Format Guide -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2 text-sm">Supported Filename Formats:</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div class="bg-white p-2 rounded">
                    <code class="text-blue-600">533113714188.jpg</code>
                    <p class="text-gray-600 mt-1">IDPEL Main (attach)</p>
                </div>
                <div class="bg-white p-2 rounded">
                    <code class="text-amber-600">533113714188(2).jpg</code>
                    <p class="text-gray-600 mt-1">Branch #2 (auto-duplicate)</p>
                </div>
                <div class="bg-white p-2 rounded">
                    <code class="text-green-600">533113714188_-6.767,108.556.jpg</code>
                    <p class="text-gray-600 mt-1">With coordinates</p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="isAnalyzing" class="text-center py-6">
            <div class="inline-flex items-center px-5 py-2.5 bg-[#29AAE1] text-white rounded-lg">
                <svg class="animate-spin -ml-1 mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Analyzing files...
            </div>
        </div>

        <!-- Results Table -->
        <div x-show="results.length > 0 && !isAnalyzing"
            class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-sm font-semibold text-gray-800">
                    Preview Upload (<span x-text="results.length"></span> files)
                </h3>
                <div class="flex gap-2">
                    <button @click="clearAll()"
                        class="px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300:bg-slate-600 transition">
                        Clear All
                    </button>
                    <button @click="processUploads()" :disabled="isProcessing"
                        class="px-4 py-1.5 text-xs bg-[#29AAE1] text-white rounded hover:bg-[#1E8CC0] transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Process All</span>
                        <span x-show="isProcessing">Processing...</span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                Preview</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                Filename</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                IDPEL</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                Action</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="(item, index) in results" :key="index">
                            <tr class="hover:bg-gray-50:bg-slate-700/50">
                                <td class="px-3 py-2">
                                    <img :src="'/storage/' + item.temp_path" class="w-10 h-10 object-cover rounded" alt="">
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs text-gray-900" x-text="item.original_name"></span>
                                    <div x-show="item.parsed.sequence"
                                        class="text-[10px] text-amber-600">
                                        Branch #<span x-text="item.parsed.sequence"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="font-mono text-xs text-gray-700"
                                        x-text="item.parsed.idpel || '-'"></span>
                                    <div x-show="item.parsed.lat" class="text-[10px] text-gray-500">
                                        <span x-text="item.parsed.lat?.toFixed(4)"></span>, <span
                                            x-text="item.parsed.lng?.toFixed(4)"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full" :class="{
                                                'bg-green-100 text-green-700': item.match.status === 'match',
                                                'bg-amber-100 text-amber-700': item.match.status === 'duplicate',
                                                'bg-red-100 text-red-700': item.match.status === 'not_found'
                                            }"
                                        x-text="item.match.status === 'match' ? 'Match' : item.match.status === 'duplicate' ? 'Duplicate' : 'Not Found'">
                                    </span>
                                    <p class="text-[10px] text-gray-500 mt-0.5" x-text="item.match.message"></p>
                                </td>
                                <td class="px-3 py-2">
                                    <select x-model="item.action"
                                        class="text-xs border rounded px-2 py-1 bg-white">
                                        <option value="attach" x-show="item.match.status === 'match'">Attach to record
                                        </option>
                                        <option value="duplicate" x-show="item.match.status === 'duplicate'">
                                            Create duplicate</option>
                                        <option value="skip">Skip</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <button @click="removeItem(index)" class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
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
            class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-green-800 mb-3">Upload Complete!</h3>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="bg-white p-3 rounded">
                    <p class="text-2xl font-bold text-green-600" x-text="processResult?.processed || 0"></p>
                    <p class="text-xs text-gray-600">Photos Attached</p>
                </div>
                <div class="bg-white p-3 rounded">
                    <p class="text-2xl font-bold text-amber-600" x-text="processResult?.duplicated || 0"></p>
                    <p class="text-xs text-gray-600">Records Created</p>
                </div>
                <div class="bg-white p-3 rounded">
                    <p class="text-2xl font-bold text-gray-500" x-text="processResult?.skipped || 0"></p>
                    <p class="text-xs text-gray-600">Skipped</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function photoUpload() {
            return {
                isDragging: false,
                isAnalyzing: false,
                isProcessing: false,
                results: [],
                processResult: null,

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
@endpush