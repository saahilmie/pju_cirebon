<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - PLN SIPJU</title>
    <link rel="icon" type="image/png" href="{{ asset('images/pln-sipju-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-[#E8F4FC] to-[#D1E9F6] flex items-center justify-center p-4" x-data="loginPage()">
    <!-- Toast Notification -->
    <div x-show="showToast" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         class="fixed top-4 right-4 z-50">
        <div class="bg-[#17C353] text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px]">
            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-[#17C353]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-semibold" x-text="toastTitle"></p>
                <p class="text-sm opacity-90" x-text="toastMessage"></p>
            </div>
        </div>
        <div class="h-1 bg-white/30 rounded-b-lg overflow-hidden -mt-1 mx-1">
            <div class="h-full bg-white transition-all duration-100" :style="{ width: toastProgress + '%' }"></div>
        </div>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden w-full max-w-md">
        <!-- Gradient Header - Blue from top center fading down -->
        <div class="relative h-28 flex items-end justify-center pb-2" style="background: radial-gradient(ellipse at top center, #29AAE1 0%, rgba(41, 170, 225, 0.3) 40%, transparent 70%);">
            <img src="{{ asset('images/pln-sipju-logo.png') }}" alt="PLN SIPJU" class="h-16 relative z-10">
        </div>
        
        <!-- Form Content -->
        <div class="p-8 pt-4">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Welcome back</h1>
                <p class="text-gray-500 text-sm">Please enter your details to sign in</p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1] text-sm" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative" x-data="{ showPassword: false }">
                        <input :type="showPassword ? 'text' : 'password'" name="password" placeholder="Password"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#29AAE1] text-sm pr-10" required>
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#29AAE1]">
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
                
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-[#29AAE1]">
                        <span class="text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-[#29AAE1] hover:underline">Forgot Password?</a>
                </div>
                
                <button type="submit" class="w-full bg-[#29AAE1] hover:bg-[#1E8CC0] text-white font-semibold py-2.5 rounded-lg transition-colors">
                    Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        function loginPage() {
            return {
                showToast: false,
                toastTitle: '',
                toastMessage: '',
                toastProgress: 100,
                toastInterval: null,
                init() {}
            };
        }
    </script>
</body>
</html>
