@extends('admin.layouts.guest')

@section('title', 'Connexion Admin')

@section('content')
<div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-8 py-6 text-center">
        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-comment-dots text-3xl text-white"></i>
        </div>
        <h1 class="text-2xl font-bold text-white">MSG Link Admin</h1>
        <p class="text-primary-100 text-sm mt-1">Connectez-vous pour accéder au dashboard</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.login.submit') }}" method="POST" class="p-8">
        @csrf

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <!-- Email -->
        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-envelope mr-1 text-gray-400"></i>
                Adresse email
            </label>
            <input type="email"
                   name="email"
                   id="email"
                   value="{{ old('email') }}"
                   required
                   autofocus
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                   placeholder="admin@msglink.com">
        </div>

        <!-- Password -->
        <div class="mb-6">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-lock mr-1 text-gray-400"></i>
                Mot de passe
            </label>
            <div class="relative">
                <input type="password"
                       name="password"
                       id="password"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors pr-12"
                       placeholder="••••••••">
                <button type="button"
                        onclick="togglePassword()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
            </label>
        </div>

        <!-- Submit Button -->
        <button type="submit"
                class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white py-3 px-4 rounded-lg font-semibold hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-200 flex items-center justify-center">
            <i class="fas fa-sign-in-alt mr-2"></i>
            Se connecter
        </button>
    </form>

    <!-- Footer -->
    <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center">
        <a href="/" class="text-sm text-gray-500 hover:text-primary-600 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i>
            Retour au site
        </a>
    </div>
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
</script>
@endsection
