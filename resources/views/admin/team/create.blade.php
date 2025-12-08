@extends('admin.layouts.app')

@section('title', 'Nouveau membre')
@section('header', 'Ajouter un membre d\'équipe')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.team.index') }}" class="text-primary-600 hover:text-primary-700">
        <i class="fas fa-arrow-left mr-2"></i>Retour à l'équipe
    </a>
</div>

<div class="max-w-2xl">
    <form action="{{ route('admin.team.store') }}" method="POST">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">Informations du membre</h2>
                <p class="text-sm text-gray-500">Créez un nouveau compte administrateur ou modérateur</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">Prénom <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" id="first_name"
                               value="{{ old('first_name') }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('first_name') border-red-500 @enderror">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" id="last_name"
                               value="{{ old('last_name') }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('last_name') border-red-500 @enderror">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Nom d'utilisateur <span class="text-red-500">*</span></label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">@</span>
                        <input type="text" name="username" id="username"
                               value="{{ old('username') }}"
                               required
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-r-lg focus:ring-primary-500 focus:border-primary-500 @error('username') border-red-500 @enderror">
                    </div>
                    @error('username')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email & Phone -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" id="email"
                               value="{{ old('email') }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone') }}"
                               placeholder="Ex: 237600000000"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Rôle <span class="text-red-500">*</span></label>
                    <select name="role" id="role" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('role') border-red-500 @enderror">
                        <option value="">Sélectionner un rôle</option>
                        <option value="moderator" {{ old('role') == 'moderator' ? 'selected' : '' }}>Modérateur</option>
                        @if(auth()->user()->is_super_admin)
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrateur</option>
                            <option value="superadmin" {{ old('role') == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                        @endif
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">
                        @if(auth()->user()->is_super_admin)
                            En tant que Super Admin, vous pouvez créer des admins et d'autres super admins.
                        @else
                            En tant qu'Administrateur, vous pouvez uniquement créer des modérateurs.
                        @endif
                    </p>
                </div>

                <!-- Password -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-4">Mot de passe</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Mot de passe <span class="text-red-500">*</span></label>
                            <input type="password" name="password" id="password"
                                   required
                                   minlength="8"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('password') border-red-500 @enderror">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   required
                                   minlength="8"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Le mot de passe doit contenir au moins 8 caractères.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                <a href="{{ route('admin.team.index') }}"
                   class="px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Créer le membre
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Info Box -->
<div class="mt-6 max-w-2xl bg-yellow-50 border border-yellow-200 rounded-xl p-4">
    <h3 class="text-sm font-semibold text-yellow-800 mb-2"><i class="fas fa-exclamation-triangle mr-2"></i>Important</h3>
    <ul class="text-sm text-yellow-700 space-y-1">
        <li>Le compte sera automatiquement vérifié et activé.</li>
        <li>Communiquez les identifiants de manière sécurisée au nouveau membre.</li>
        <li>Il est recommandé de demander au nouveau membre de changer son mot de passe lors de sa première connexion.</li>
    </ul>
</div>
@endsection
