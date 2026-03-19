@extends('admin.layouts.guest')

@section('title', 'Connexion Admin')

@section('content')
<style>
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }

    .shimmer {
        background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
        background-size: 1000px 100%;
        animation: shimmer 3s infinite;
    }

    @keyframes pulse-soft {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.8;
        }
    }

    .pulse-soft {
        animation: pulse-soft 2s ease-in-out infinite;
    }

    /* Input focus effects */
    .input-modern {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .input-modern:focus {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(192, 38, 211, 0.3);
    }

    /* Button hover effects */
    .btn-modern {
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .btn-modern::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-modern:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px -5px rgba(192, 38, 211, 0.5);
    }

    .btn-modern:active {
        transform: translateY(0);
    }
</style>

<div class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/20">
    <!-- Header with animated logo -->
    <div class="bg-gradient-to-br from-primary-600 via-purple-600 to-primary-700 px-8 py-8 text-center relative overflow-hidden">
        <div class="absolute inset-0 shimmer"></div>

        <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 pulse-soft relative z-10 backdrop-blur-sm border border-white/30">
            <i class="fas fa-comment-dots text-4xl text-white"></i>
        </div>

        <h1 class="text-3xl font-bold text-white relative z-10 tracking-tight">Weylo Admin</h1>
        <p class="text-primary-100 text-sm mt-2 relative z-10 font-medium">Connectez-vous pour accéder au dashboard</p>

        <!-- Decorative elements -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full -ml-12 -mb-12"></div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.login.submit') }}" method="POST" class="p-8 bg-white rounded-b-3xl">
        @csrf

        @if($errors->any())
            <div class="mb-6 bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 text-red-700 px-4 py-4 rounded-lg text-sm shadow-sm animate-shake">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium">{{ $errors->first() }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Email -->
        <div class="mb-6 group">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2 group-hover:text-primary-600 transition-colors">
                <i class="fas fa-envelope mr-2 text-primary-500"></i>
                Adresse email
            </label>
            <div class="relative">
                <input type="email"
                       name="email"
                       id="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       class="input-modern w-full px-4 py-3.5 pl-11 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300"
                       placeholder="admin@weylo.com">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="mb-6 group">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2 group-hover:text-primary-600 transition-colors">
                <i class="fas fa-lock mr-2 text-primary-500"></i>
                Mot de passe
            </label>
            <div class="relative">
                <input type="password"
                       name="password"
                       id="password"
                       required
                       class="input-modern w-full px-4 py-3.5 pl-11 pr-12 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300"
                       placeholder="••••••••">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <i class="fas fa-lock"></i>
                </div>
                <button type="button"
                        onclick="togglePassword()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600 transition-colors focus:outline-none">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mb-8">
            <label class="flex items-center group cursor-pointer">
                <input type="checkbox"
                       name="remember"
                       class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 focus:ring-2 transition-all">
                <span class="ml-3 text-sm text-gray-600 group-hover:text-gray-900 transition-colors font-medium">
                    Se souvenir de moi
                </span>
            </label>
        </div>

        <!-- Submit Button -->
        <button type="submit"
                class="btn-modern w-full bg-gradient-to-r from-primary-600 via-purple-600 to-primary-700 text-white py-4 px-6 rounded-xl font-bold text-base hover:shadow-2xl focus:outline-none focus:ring-4 focus:ring-primary-500/50 transition-all duration-300 flex items-center justify-center relative z-10">
            <i class="fas fa-sign-in-alt mr-3 text-lg"></i>
            <span>Se connecter</span>
        </button>

        <!-- Divider -->
        <div class="relative my-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500 font-medium">Weylo Administration</span>
            </div>
        </div>

        <!-- Back to site link -->
        <div class="text-center">
            <a href="/" class="inline-flex items-center text-sm text-gray-600 hover:text-primary-600 transition-colors font-medium group">
                <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                Retour au site
            </a>
        </div>
    </form>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Add shake animation on error
@if($errors->any())
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .animate-shake {
            animation: shake 0.5s ease-in-out;
        }
    `;
    document.head.appendChild(style);
@endif
</script>
@endsection
