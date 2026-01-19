@extends('layouts.app')

@section('title', 'Account Setting')
@section('page-title', 'Account Setting')

@push('styles')
<style>
    .crop-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: #000;
    }
    .crop-circle-mask {
        position: absolute;
        border-radius: 50%;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7);
        border: 2px solid rgba(255, 255, 255, 0.3);
        pointer-events: none;
    }
    .crop-image-container {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: grab;
    }
    .crop-image-container:active {
        cursor: grabbing;
    }
    .crop-image-container img {
        user-select: none;
        -webkit-user-drag: none;
    }
</style>
@endpush

@section('content')
<div x-data="profilePage()" x-init="init()" class="min-h-[calc(100vh-120px)] py-6 px-4 sm:px-6 lg:px-8">
    <!-- Toast Notification -->
    <div x-show="showToast" x-cloak x-transition class="fixed top-4 right-4 z-[60]">
        <div :class="toastType === 'success' ? 'bg-[#17C353]' : 'bg-[#EB2027]'" 
            class="text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[280px]">
            <div class="w-7 h-7 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                <svg x-show="toastType === 'success'" class="w-4 h-4 text-[#17C353]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg x-show="toastType === 'error'" class="w-4 h-4 text-[#EB2027]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <p class="font-medium text-sm" x-text="toastMessage"></p>
        </div>
    </div>

    <!-- Fullscreen Image Cropper Modal - WhatsApp Style -->
    <div x-show="showCropperModal" x-cloak class="crop-overlay" 
         @mousedown="startDrag($event)" 
         @mousemove="onDrag($event)" 
         @mouseup="endDrag()"
         @mouseleave="endDrag()"
         @touchstart="startDrag($event)"
         @touchmove="onDrag($event)"
         @touchend="endDrag()"
         @wheel="onZoom($event)">
        
        <!-- Header -->
        <div class="absolute top-0 left-0 right-0 z-10 flex items-center justify-between px-4 py-4">
            <button @click="closeCropper()" class="text-white hover:text-gray-300 p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <span class="text-white font-medium">Drag the image to adjust</span>
            <span class="w-10"></span>
        </div>

        <!-- Image Container -->
        <div class="crop-image-container" id="cropImageContainer">
            <img id="cropperImage" src="" alt="Crop preview" 
                 :style="'transform: translate(' + cropTranslateX + 'px, ' + cropTranslateY + 'px) scale(' + cropScale + '); transition: ' + (isDragging ? 'none' : 'transform 0.1s')"
                 class="max-w-none">
        </div>

        <!-- Circle Mask -->
        <div class="crop-circle-mask" id="cropCircleMask"
             :style="'width: ' + circleSize + 'px; height: ' + circleSize + 'px; top: 50%; left: 50%; transform: translate(-50%, -50%)'">
        </div>

        <!-- Zoom Controls -->
        <div class="absolute right-4 top-1/2 -translate-y-1/2 z-10 flex flex-col gap-2">
            <button @click="zoomIn()" class="w-10 h-10 bg-white/20 backdrop-blur rounded-lg flex items-center justify-center text-white hover:bg-white/30 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <button @click="zoomOut()" class="w-10 h-10 bg-white/20 backdrop-blur rounded-lg flex items-center justify-center text-white hover:bg-white/30 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </button>
        </div>

        <!-- Apply Button -->
        <button @click="applyCrop()" 
                class="absolute bottom-8 right-8 z-10 w-14 h-14 bg-[#29AAE1] rounded-full flex items-center justify-center shadow-lg hover:bg-[#1E8CC0] transition-all hover:scale-105">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
        </button>
    </div>

    <div class="max-w-4xl mx-auto">
        <!-- Account Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-6">Account</h2>
            
            <!-- Profile Picture -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 pb-6 border-b border-gray-200">
                <div class="relative">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-gray-200">
                        <template x-if="avatarPreview">
                            <img :src="avatarPreview" alt="Profile" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarPreview && user.avatar">
                            <img :src="'{{ asset('storage') }}/' + user.avatar" alt="Profile" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!avatarPreview && !user.avatar">
                            <svg class="w-12 h-12 sm:w-14 sm:h-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </template>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800">Profile picture</h3>
                    <p class="text-sm text-gray-500 mb-3">PNG, JPEG under 15MB (will be cropped to circle)</p>
                    <div class="flex flex-wrap gap-2">
                        <label class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors">
                            Upload new picture
                            <input type="file" class="hidden" accept="image/png,image/jpeg,image/jpg" @change="handleAvatarChange($event)">
                        </label>
                        <button @click="deleteAvatar()" x-show="user.avatar || avatarPreview"
                            class="px-4 py-2 bg-[#EB2027] text-white rounded-lg text-sm font-medium hover:bg-red-600 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Full Name -->
            <div class="py-6 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800 mb-4">Full name</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2">First name</label>
                        <input type="text" x-model="form.first_name" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1] outline-none transition-all"
                            placeholder="Enter first name">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2">Last name</label>
                        <input type="text" x-model="form.last_name"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1] outline-none transition-all"
                            placeholder="Enter last name">
                    </div>
                </div>
            </div>

            <!-- Contact Email & Role -->
            <div class="py-6 border-b border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-4">Contact email</h3>
                        <label class="block text-sm text-gray-600 mb-2">Email</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <input type="email" :value="user.email" disabled
                                class="w-full pl-12 pr-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-4">Role</h3>
                        <label class="block text-sm text-gray-600 mb-2">Your role</label>
                        <input type="text" :value="user.role || 'User'" disabled
                            class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed capitalize">
                    </div>
                </div>
            </div>

            <!-- Password -->
            <div class="pt-6">
                <h3 class="font-semibold text-gray-800 mb-4">Password</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-2">New password</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input :type="showPassword ? 'text' : 'password'" x-model="form.password"
                                class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1] outline-none transition-all"
                                placeholder="Enter new password">
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-2">Confirm password</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input :type="showConfirmPassword ? 'text' : 'password'" x-model="form.password_confirmation"
                                class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1] outline-none transition-all"
                                placeholder="Confirm new password">
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">Leave empty if you don't want to change your password</p>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('dashboard') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button @click="saveProfile()" :disabled="isSaving"
                class="px-8 py-2.5 bg-[#29AAE1] text-white rounded-lg font-medium hover:bg-[#1E8CC0] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <svg x-show="isSaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="isSaving ? 'Saving...' : 'Save Changes'"></span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function profilePage() {
    return {
        user: @json($user),
        form: {
            first_name: '',
            last_name: '',
            password: '',
            password_confirmation: ''
        },
        avatarPreview: null,
        avatarFile: null,
        showPassword: false,
        showConfirmPassword: false,
        showToast: false,
        toastMessage: '',
        toastType: 'success',
        isSaving: false,
        
        // Cropper state
        showCropperModal: false,
        originalImageSrc: null,
        cropScale: 1,
        cropTranslateX: 0,
        cropTranslateY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        lastTranslateX: 0,
        lastTranslateY: 0,
        circleSize: 280,
        imageNaturalWidth: 0,
        imageNaturalHeight: 0,

        init() {
            const nameParts = this.user.name ? this.user.name.split(' ') : [''];
            this.form.first_name = nameParts[0] || '';
            this.form.last_name = nameParts.slice(1).join(' ') || '';
            
            // Set circle size based on viewport
            this.updateCircleSize();
            window.addEventListener('resize', () => this.updateCircleSize());

            @if(session('success'))
                this.showSuccessToast("{{ session('success') }}");
            @endif
        },

        updateCircleSize() {
            const minDimension = Math.min(window.innerWidth, window.innerHeight);
            this.circleSize = Math.min(300, minDimension * 0.7);
        },

        handleAvatarChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                this.showErrorToast('Please upload a valid image (PNG or JPEG)');
                event.target.value = '';
                return;
            }

            if (file.size > 15 * 1024 * 1024) {
                this.showErrorToast('Image size must be under 15MB');
                event.target.value = '';
                return;
            }

            this.avatarFile = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.originalImageSrc = e.target.result;
                
                // Load image to get dimensions
                const img = new Image();
                img.onload = () => {
                    this.imageNaturalWidth = img.naturalWidth;
                    this.imageNaturalHeight = img.naturalHeight;
                    
                    // Set initial scale so image fills the circle
                    const minImageDim = Math.min(img.naturalWidth, img.naturalHeight);
                    this.cropScale = (this.circleSize / minImageDim) * 1.2;
                    this.cropTranslateX = 0;
                    this.cropTranslateY = 0;
                    this.lastTranslateX = 0;
                    this.lastTranslateY = 0;
                    
                    // Show cropper
                    document.getElementById('cropperImage').src = e.target.result;
                    this.showCropperModal = true;
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
            event.target.value = '';
        },

        startDrag(event) {
            if (event.target.tagName === 'BUTTON' || event.target.closest('button')) return;
            
            this.isDragging = true;
            const point = event.touches ? event.touches[0] : event;
            this.dragStartX = point.clientX - this.cropTranslateX;
            this.dragStartY = point.clientY - this.cropTranslateY;
        },

        onDrag(event) {
            if (!this.isDragging) return;
            event.preventDefault();
            
            const point = event.touches ? event.touches[0] : event;
            this.cropTranslateX = point.clientX - this.dragStartX;
            this.cropTranslateY = point.clientY - this.dragStartY;
        },

        endDrag() {
            this.isDragging = false;
            this.lastTranslateX = this.cropTranslateX;
            this.lastTranslateY = this.cropTranslateY;
        },

        onZoom(event) {
            event.preventDefault();
            const delta = event.deltaY > 0 ? -0.1 : 0.1;
            this.cropScale = Math.max(0.5, Math.min(5, this.cropScale + delta));
        },

        zoomIn() {
            this.cropScale = Math.min(5, this.cropScale + 0.2);
        },

        zoomOut() {
            this.cropScale = Math.max(0.5, this.cropScale - 0.2);
        },

        closeCropper() {
            this.showCropperModal = false;
            this.cropScale = 1;
            this.cropTranslateX = 0;
            this.cropTranslateY = 0;
        },

        applyCrop() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const size = 400; // Output size
            canvas.width = size;
            canvas.height = size;

            const img = document.getElementById('cropperImage');
            
            // Calculate the crop area
            const containerRect = document.getElementById('cropImageContainer').getBoundingClientRect();
            const imgRect = img.getBoundingClientRect();
            
            // Center of the circle mask in container coords
            const circleCenterX = containerRect.width / 2;
            const circleCenterY = containerRect.height / 2;
            
            // Calculate what part of the original image is in the circle
            const imgDisplayWidth = img.naturalWidth * this.cropScale;
            const imgDisplayHeight = img.naturalHeight * this.cropScale;
            
            // Image position in container
            const imgCenterX = containerRect.width / 2 + this.cropTranslateX;
            const imgCenterY = containerRect.height / 2 + this.cropTranslateY;
            const imgLeft = imgCenterX - imgDisplayWidth / 2;
            const imgTop = imgCenterY - imgDisplayHeight / 2;
            
            // Circle area in image coordinates
            const circleLeft = circleCenterX - this.circleSize / 2;
            const circleTop = circleCenterY - this.circleSize / 2;
            
            // Source coordinates in original image
            const srcX = (circleLeft - imgLeft) / this.cropScale;
            const srcY = (circleTop - imgTop) / this.cropScale;
            const srcSize = this.circleSize / this.cropScale;

            // Draw the cropped area
            ctx.drawImage(
                img,
                srcX, srcY, srcSize, srcSize,
                0, 0, size, size
            );

            canvas.toBlob((blob) => {
                this.avatarFile = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                this.avatarPreview = canvas.toDataURL('image/jpeg', 0.9);
                this.closeCropper();
            }, 'image/jpeg', 0.9);
        },

        async deleteAvatar() {
            try {
                const res = await fetch('/profile/avatar', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (res.ok) {
                    this.user.avatar = null;
                    this.avatarPreview = null;
                    this.avatarFile = null;
                    this.showSuccessToast('Profile picture deleted');
                }
            } catch (e) {
                this.showErrorToast('Failed to delete profile picture');
            }
        },

        async saveProfile() {
            if (!this.form.first_name) {
                this.showErrorToast('First name is required');
                return;
            }

            if (this.form.password && this.form.password !== this.form.password_confirmation) {
                this.showErrorToast('Passwords do not match');
                return;
            }

            if (this.form.password && this.form.password.length < 8) {
                this.showErrorToast('Password must be at least 8 characters');
                return;
            }

            this.isSaving = true;

            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('first_name', this.form.first_name);
            formData.append('last_name', this.form.last_name || '');

            if (this.form.password) {
                formData.append('password', this.form.password);
                formData.append('password_confirmation', this.form.password_confirmation);
            }

            if (this.avatarFile) {
                formData.append('avatar', this.avatarFile);
            }

            try {
                const res = await fetch('/profile', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const result = await res.json();

                if (res.ok) {
                    this.showSuccessToast('Profile updated successfully!');
                    this.form.password = '';
                    this.form.password_confirmation = '';
                    this.avatarFile = null;

                    if (result.user) {
                        this.user = result.user;
                        if (result.user.avatar) {
                            this.avatarPreview = null;
                        }
                    }
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    this.showErrorToast(result.message || 'Failed to update profile');
                }
            } catch (e) {
                this.showErrorToast('An error occurred while saving');
            } finally {
                this.isSaving = false;
            }
        },

        showSuccessToast(message) {
            this.toastMessage = message;
            this.toastType = 'success';
            this.showToast = true;
            setTimeout(() => this.showToast = false, 3000);
        },

        showErrorToast(message) {
            this.toastMessage = message;
            this.toastType = 'error';
            this.showToast = true;
            setTimeout(() => this.showToast = false, 3000);
        }
    };
}
</script>
@endpush
@endsection