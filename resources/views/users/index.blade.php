@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'Users Management')

@section('content')
<div x-data="userManagement()" x-init="init()" class="space-y-6">
    <!-- Toast Notification -->
    <div x-show="showToast" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         class="fixed top-4 right-4 z-[60]">
        <div :class="toastType === 'success' ? 'bg-[#17C353]' : 'bg-[#EB2027]'" 
             class="text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px]">
            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                <svg x-show="toastType === 'success'" class="w-5 h-5 text-[#17C353]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg x-show="toastType === 'error'" class="w-5 h-5 text-[#EB2027]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold" x-text="toastTitle"></p>
                <p class="text-sm opacity-90" x-text="toastMessage"></p>
            </div>
            <button @click="showToast = false" class="text-white/80 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="h-1 bg-white/30 rounded-b-lg overflow-hidden -mt-1 mx-1">
            <div class="h-full bg-white transition-all duration-100" :style="{ width: toastProgress + '%' }"></div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-700">Users List</h2>
        <div class="flex items-center gap-3">
            <button @click="exportExcel()" class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Export Excel
            </button>
            <button @click="openAddModal()" class="flex items-center gap-2 px-4 py-2 border-2 border-[#29AAE1] text-[#29AAE1] rounded-lg hover:bg-[#29AAE1] hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add User
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 max-w-xs">
            <label class="block text-sm text-gray-600 mb-1">Search</label>
            <div class="relative">
                <input type="text" 
                       x-model="searchQuery"
                       @keydown.enter.prevent="applyFilters()"
                       placeholder="Search by name or email" 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1]">
                <svg class="w-5 h-5 text-[#29AAE1] absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Status</label>
            <div class="flex rounded-lg overflow-hidden border border-gray-300">
                <button @click="statusFilter = 'all'; applyFilters()" 
                        :class="statusFilter === 'all' ? 'bg-[#29AAE1] text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="px-4 py-2 text-sm font-medium transition-colors">All</button>
                <button @click="statusFilter = 'active'; applyFilters()" 
                        :class="statusFilter === 'active' ? 'bg-[#29AAE1] text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="px-4 py-2 text-sm font-medium transition-colors border-l border-gray-300">Active</button>
                <button @click="statusFilter = 'deactive'; applyFilters()" 
                        :class="statusFilter === 'deactive' ? 'bg-[#29AAE1] text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                        class="px-4 py-2 text-sm font-medium transition-colors border-l border-gray-300">Deactive</button>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Role</label>
            <select x-model="roleFilter" @change="applyFilters()" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1]">
                <option value="all">All</option>
                <option value="super_admin">Super Admin</option>
                <option value="admin">Admin</option>
                <option value="employee">Employee</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-[#29AAE1] text-white">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold">NO</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">FULL NAME</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">EMAIL</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">STATUS</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">ROLE</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">ACTION</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($users as $index => $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $users->firstItem() + $index }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button class="p-1 text-gray-500 hover:text-[#29AAE1]" title="View">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button @click="openEditModal({{ json_encode($user) }})" class="p-1 text-gray-500 hover:text-[#FBED21]" title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                            <button @click="openDeleteModal('{{ $user->id }}', '{{ $user->name }}', '{{ $user->email }}')" class="p-1 text-gray-500 hover:text-[#EB2027]" title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No users found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-end">
        {{ $users->withQueryString()->links() }}
    </div>

    <!-- Add/Edit User Modal -->
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showModal = false">
        <div x-show="showModal" x-transition class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-800" x-text="editingUser ? 'Edit User' : 'Add User'"></h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form :action="editingUser ? '/users/' + editingUser.id : '{{ route('users.store') }}'" method="POST" class="p-4 space-y-4">
                @csrf
                <template x-if="editingUser">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                    <input type="text" name="name" x-model="formData.name" placeholder="Enter name" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1]" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" x-model="formData.email" placeholder="Enter email" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1]" required>
                    <p class="text-xs text-gray-500 mt-1">Only @pln.co.id or @mhs.unsoed.ac.id allowed</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input :type="showPasswordField ? 'text' : 'password'" name="password" x-model="formData.password" placeholder="Enter password" 
                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1]" :required="!editingUser">
                        <button type="button" @click="showPasswordField = !showPasswordField" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPasswordField" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPasswordField" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <p x-show="editingUser" class="text-xs text-gray-500 mt-1">Leave empty to keep current password</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" x-model="formData.role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1]">
                            <option value="">Select</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" x-model="formData.status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1]">
                            <option value="active">Active</option>
                            <option value="deactive">Deactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-6 py-2 bg-[#29AAE1] text-white rounded-lg hover:bg-[#1E8CC0]" x-text="editingUser ? 'Update User' : 'Add User'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div x-show="showDeleteModal" x-transition class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#EB2027]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Delete Confirmation</h3>
            <p class="text-gray-600 mb-4">Are you sure you want to delete this user?</p>
            <div class="bg-gray-100 rounded-lg px-4 py-2 mb-4">
                <p class="text-sm font-medium text-gray-800" x-text="deleteUserName"></p>
                <p class="text-xs text-gray-500" x-text="deleteUserEmail"></p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-2 mb-6 flex items-center gap-2 justify-center">
                <svg class="w-4 h-4 text-[#EB2027]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-sm text-[#EB2027]">This action cannot be undone.</span>
            </div>
            <div class="flex gap-3">
                <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <form :action="'/users/' + deleteUserId" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-[#EB2027] text-white rounded-lg hover:bg-red-700">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div x-show="showLogoutModal" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div x-show="showLogoutModal" x-transition class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#EB2027]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Log Out Confirmation</h3>
            <p class="text-gray-600 mb-6">Are you sure you want to log out?</p>
            <div class="flex gap-3">
                <button @click="showLogoutModal = false" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                <form action="{{ route('logout') }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-[#EB2027] text-white rounded-lg hover:bg-red-700">Yes, Log Out</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function userManagement() {
    return {
        showModal: false,
        showDeleteModal: false,
        showLogoutModal: false,
        showPasswordField: false,
        showToast: false,
        toastTitle: '',
        toastMessage: '',
        toastType: 'success',
        toastProgress: 100,
        toastInterval: null,
        
        // Initialize from URL params
        searchQuery: '{{ request('search', '') }}',
        statusFilter: '{{ request('status', 'all') }}',
        roleFilter: '{{ request('role', 'all') }}',
        
        editingUser: null,
        deleteUserId: null,
        deleteUserName: '',
        deleteUserEmail: '',
        
        formData: {
            name: '',
            email: '',
            password: '',
            role: '',
            status: 'active'
        },

        init() {
            @if(session('success'))
                this.notify('{{ session('success') }}', '', 'success');
            @endif
            @if($errors->any())
                this.notify('Error', '{{ $errors->first() }}', 'error');
            @endif
            
            // Expose logout modal to global for sidebar
            window.openLogoutModal = () => { this.showLogoutModal = true; };
        },

        openAddModal() {
            this.editingUser = null;
            this.formData = { name: '', email: '', password: '', role: '', status: 'active' };
            this.showPasswordField = false;
            this.showModal = true;
        },

        openEditModal(user) {
            this.editingUser = user;
            this.formData = {
                name: user.name,
                email: user.email,
                password: '',
                role: user.role,
                status: user.status
            };
            this.showPasswordField = false;
            this.showModal = true;
        },

        openDeleteModal(id, name, email) {
            this.deleteUserId = id;
            this.deleteUserName = name;
            this.deleteUserEmail = email;
            this.showDeleteModal = true;
        },

        applyFilters() {
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.statusFilter !== 'all') params.set('status', this.statusFilter);
            if (this.roleFilter !== 'all') params.set('role', this.roleFilter);
            window.location.href = '{{ route('users.index') }}?' + params.toString();
        },

        exportExcel() {
            // Create export URL with current filters
            const params = new URLSearchParams();
            if (this.searchQuery) params.set('search', this.searchQuery);
            if (this.statusFilter !== 'all') params.set('status', this.statusFilter);
            if (this.roleFilter !== 'all') params.set('role', this.roleFilter);
            params.set('export', 'excel');
            window.location.href = '{{ route('users.index') }}?' + params.toString();
        },

        notify(title, message, type = 'success') {
            this.toastTitle = title;
            this.toastMessage = message;
            this.toastType = type;
            this.toastProgress = 100;
            this.showToast = true;

            clearInterval(this.toastInterval);
            this.toastInterval = setInterval(() => {
                this.toastProgress -= 2;
                if (this.toastProgress <= 0) {
                    this.showToast = false;
                    clearInterval(this.toastInterval);
                }
            }, 100);
        }
    };
}
</script>
@endpush
@endsection
