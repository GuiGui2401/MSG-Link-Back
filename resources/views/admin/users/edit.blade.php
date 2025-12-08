@extends('admin.layouts.app')

@section('title', 'Modifier - ' . $user->username)
@section('header', 'Modifier l\'utilisateur')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.show', $user) }}" class="text-primary-600 hover:text-primary-700">
        <i class="fas fa-arrow-left mr-2"></i>Retour au profil
    </a>
</div>

<div class="max-w-3xl">
    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">Informations de l'utilisateur</h2>
                <p class="text-sm text-gray-500">Modifiez les informations de {{ $user->username }}</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Avatar Section -->
                <div class="flex items-center space-x-6">
                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                        @if($user->avatar)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-20 h-20 object-cover">
                        @else
                            <span class="text-3xl font-bold text-gray-400">{{ $user->initial }}</span>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo de profil</label>
                        <input type="file" name="avatar" accept="image/*"
                               class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                    </div>
                </div>

                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                        <input type="text" name="first_name" id="first_name"
                               value="{{ old('first_name', $user->first_name) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('first_name') border-red-500 @enderror">
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                        <input type="text" name="last_name" id="last_name"
                               value="{{ old('last_name', $user->last_name) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('last_name') border-red-500 @enderror">
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Nom d'utilisateur</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">@</span>
                        <input type="text" name="username" id="username"
                               value="{{ old('username', $user->username) }}"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-r-lg focus:ring-primary-500 focus:border-primary-500 @error('username') border-red-500 @enderror">
                    </div>
                    @error('username')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email & Phone -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" id="email"
                               value="{{ old('email', $user->email) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone', $user->phone) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Bio -->
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                    <textarea name="bio" id="bio" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('bio') border-red-500 @enderror"
                              placeholder="Bio de l'utilisateur...">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
                    @php
                        $currentUser = auth()->user();
                        $isSelf = $user->id === $currentUser->id;
                        $canChangeRole = !$isSelf || $currentUser->is_super_admin;
                    @endphp
                    @if($canChangeRole && $currentUser->canManage($user))
                        <select name="role" id="role"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('role') border-red-500 @enderror">
                            <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>Utilisateur</option>
                            @if($currentUser->role === 'admin' || $currentUser->is_super_admin)
                                <option value="moderator" {{ old('role', $user->role) == 'moderator' ? 'selected' : '' }}>Modérateur</option>
                            @endif
                            @if($currentUser->is_super_admin)
                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrateur</option>
                                <option value="superadmin" {{ old('role', $user->role) == 'superadmin' ? 'selected' : '' }}>Super Admin</option>
                            @endif
                        </select>
                    @else
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <p class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700">
                            {{ $user->role_label }}
                            <span class="text-xs text-gray-500 ml-2">(non modifiable)</span>
                        </p>
                    @endif
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Verification Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_verified" id="is_verified" value="1"
                               {{ old('is_verified', $user->is_verified) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="is_verified" class="ml-2 text-sm text-gray-700">Compte vérifié</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="email_verified" id="email_verified" value="1"
                               {{ old('email_verified', $user->email_verified_at) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="email_verified" class="ml-2 text-sm text-gray-700">Email vérifié</label>
                    </div>
                </div>

                <!-- Password Reset -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-4">Changer le mot de passe</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                            <input type="password" name="password" id="password"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('password') border-red-500 @enderror"
                                   placeholder="Laisser vide pour ne pas changer">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="Confirmer le nouveau mot de passe">
                        </div>
                    </div>
                </div>

                <!-- Wallet Balance (Admin only) -->
                @if(auth()->user()->is_admin)
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-4">Portefeuille</h3>
                    <div>
                        <label for="wallet_balance" class="block text-sm font-medium text-gray-700 mb-2">Solde (FCFA)</label>
                        <input type="number" name="wallet_balance" id="wallet_balance"
                               value="{{ old('wallet_balance', $user->wallet_balance) }}"
                               min="0" step="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('wallet_balance') border-red-500 @enderror">
                        @error('wallet_balance')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Attention: Modifier le solde directement peut causer des incohérences.</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                </button>
            </div>
        </div>
    </form>

    <!-- Danger Zone - Only show if current user can manage this user AND it's not themselves -->
    @if(auth()->user()->canManage($user) && $user->id !== auth()->id())
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-red-200 bg-red-50">
            <h2 class="text-lg font-semibold text-red-800">Zone dangereuse</h2>
        </div>
        <div class="p-6 space-y-4">
            @if(!$user->is_banned)
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Bannir l'utilisateur</h3>
                        <p class="text-sm text-gray-500">L'utilisateur ne pourra plus accéder à son compte.</p>
                    </div>
                    <button onclick="document.getElementById('banModal').classList.remove('hidden')"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Bannir
                    </button>
                </div>
            @else
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">Débannir l'utilisateur</h3>
                        <p class="text-sm text-gray-500">Rétablir l'accès au compte.</p>
                    </div>
                    <form action="{{ route('admin.users.unban', $user) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Débannir
                        </button>
                    </form>
                </div>
            @endif

            <div class="border-t border-gray-200 pt-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900">Supprimer le compte</h3>
                    <p class="text-sm text-gray-500">Cette action est irréversible.</p>
                </div>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                      onsubmit="return confirm('Êtes-vous vraiment sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-800 text-white rounded-lg hover:bg-red-900 transition-colors">
                        Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
    @elseif($user->id === auth()->id())
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden">
        <div class="px-6 py-4 bg-blue-50">
            <p class="text-sm text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>
                Vous ne pouvez pas vous bannir ou vous supprimer vous-même.
            </p>
        </div>
    </div>
    @elseif(!auth()->user()->canManage($user))
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50">
            <p class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-2"></i>
                Vous n'avez pas les permissions nécessaires pour effectuer des actions sur cet utilisateur.
            </p>
        </div>
    </div>
    @endif
</div>

<!-- Ban Modal -->
<div id="banModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Bannir l'utilisateur</h3>
        </div>
        <form action="{{ route('admin.users.ban', $user) }}" method="POST">
            @csrf
            <div class="p-6">
                <p class="text-gray-600 mb-4">Êtes-vous sûr de vouloir bannir <strong>{{ $user->username }}</strong> ?</p>
                <div class="mb-4">
                    <label for="banned_reason" class="block text-sm font-medium text-gray-700 mb-2">Raison (optionnelle)</label>
                    <textarea name="banned_reason" id="banned_reason" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                              placeholder="Indiquez la raison du bannissement..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-xl">
                <button type="button" onclick="document.getElementById('banModal').classList.add('hidden')"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-200 rounded-lg transition-colors">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Confirmer le bannissement
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
