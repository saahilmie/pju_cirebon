@extends('layouts.app')

@section('title', 'Account Setting')
@section('page-title', 'Account Setting')

@section('content')
    <div x-data="profilePage()" x-init="init()" class="min-h-[calc(100vh-120px)] py-6 px-4 sm:px-6 lg:px-8">
        <!-- Toast Notification -->
        <div x-show="showToast" x-cloak x-transition class="fixed top-4 right-4 z-[60]">
            <div :class="toastType === 'success' ? 'bg-[#17C353]' : 'bg-[#EB2027]'"
                class="text-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[280px]">
                <div class="w-7 h-7 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                    <svg x-show="toastType === 'success'" class="w-4 h-4 text-[#17C353]" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="toastType === 'error'" class="w-4 h-4 text-[#EB2027]" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <p class="font-medium text-sm" x-text="toastMessage"></p>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">
            <!-- Account Section -->
            <div class="bg-white rounded-2xl shadow-lg p-6 sm:p-8 mb-6">
                <h2 class="text-lg font-bold text-gray-800 mb-6">Account</h2>

                <!-- Profile Picture -->
                <div
                    class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6 pb-6 border-b border-gray-200">
                    <div class="relative">
                        <div
                            class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-gray-200">
                            <template x-if="avatarPreview || user.avatar">
                                <img :src="avatarPreview || '/storage/' + user.avatar" alt="Profile"
                                    class="w-full h-full object-cover">
                            </template>
                            <template x-if="!avatarPreview && !user.avatar">
                                <svg class="w-12 h-12 sm:w-14 sm:h-14 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </template>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-800">Profile picture</h3>
                        <p class="text-sm text-gray-500 mb-3">PNG, JPEG under 15MB</p>
                        <div class="flex flex-wrap gap-2">
                            <label
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors">
                                Upload new picture
                                <input type="file" class="hidden" accept="image/png,image/jpeg,image/jpg"
                                    @change="handleAvatarChange($event)">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <input type="email" x-model="form.email"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1] outline-none transition-all"
                                    placeholder="Enter your email">
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-4">Role</h3>
                            <label class="block text-sm text-gray-600 mb-2">Your role</label>
                            <input type="text" :value="user.role || 'User'" disabled
                                class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <input :type="showPassword ? 'text' : 'password'" x-model="form.password"
                                    class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1] outline-none transition-all"
                                    placeholder="Enter new password">
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-2">Confirm password</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <input :type="showConfirmPassword ? 'text' : 'password'"
                                    x-model="form.password_confirmation"
                                    class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#29AAE1] focus:border-[#29AAE1] outline-none transition-all"
                                    placeholder="Confirm new password">
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
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
                <a href="{{ route('dashboard') }}"
                    class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
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
                        email: '',
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

                    init() {
                        // Parse name into first and last
                        const nameParts = this.user.name ? this.user.name.split(' ') : [''];
                        this.form.first_name = nameParts[0] || '';
                        this.form.last_name = nameParts.slice(1).join(' ') || '';
                        this.form.email = this.user.email || '';

                        @if(session('success'))
                            this.showSuccessToast("{{ session('success') }}");
                        @endif
                },

                    handleAvatarChange(event) {
                        const file = event.target.files[0];
                        if (!file) return;

                        // Validate file type and size
                        if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                            this.showErrorToast('Please upload a valid image (PNG or JPEG)');
                            return;
                        }

                        if (file.size > 15 * 1024 * 1024) {
                            this.showErrorToast('Image size must be under 15MB');
                            return;
                        }

                        this.avatarFile = file;
                        this.avatarPreview = URL.createObjectURL(file);
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

                        if (!this.form.email) {
                            this.showErrorToast('Email is required');
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
                        formData.append('last_name', this.form.last_name);
                        formData.append('email', this.form.email);

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

                                // Update user data
                                if (result.user) {
                                    this.user = result.user;
                                }
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