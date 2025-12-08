@extends('admin.layouts.app')

@section('title', 'Mon Profil')
@section('header', 'Mon Profil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Profile Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-8">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                <!-- Avatar Section -->
                <div class="relative group">
                    <div class="w-28 h-28 bg-white rounded-full flex items-center justify-center shadow-lg overflow-hidden">
                        @if($user->avatar)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-28 h-28 object-cover">
                        @else
                            <span class="text-5xl font-bold text-primary-600">{{ $user->initial }}</span>
                        @endif
                    </div>
                    @if($user->avatar)
                        <form action="{{ route('admin.profile.avatar.delete') }}" method="POST" class="absolute -bottom-1 -right-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg transition-colors"
                                    title="Supprimer la photo"
                                    onclick="return confirm('Supprimer la photo de profil ?')">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </form>
                    @endif
                </div>

                <!-- User Info -->
                <div class="text-center sm:text-left flex-1">
                    <h2 class="text-2xl font-bold text-white">{{ $user->full_name }}</h2>
                    <p class="text-primary-100 text-lg">{{ '@' . $user->username }}</p>
                    <div class="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                            <i class="fas fa-user-shield mr-2"></i>
                            {{ $user->role_label }}
                        </span>
                        @if($user->is_verified)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-100">
                                <i class="fas fa-check-circle mr-2"></i>
                                Vérifié
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Info -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex flex-wrap justify-center sm:justify-start gap-6 text-sm text-gray-600">
                <div class="flex items-center">
                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                    {{ $user->email }}
                    @if($user->email_verified_at)
                        <i class="fas fa-check-circle text-green-500 ml-1" title="Email vérifié"></i>
                    @endif
                </div>
                @if($user->phone)
                    <div class="flex items-center">
                        <i class="fas fa-phone mr-2 text-gray-400"></i>
                        {{ $user->phone }}
                    </div>
                @endif
                <div class="flex items-center">
                    <i class="fas fa-calendar mr-2 text-gray-400"></i>
                    Inscrit le {{ $user->created_at->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-user-edit mr-2 text-primary-500"></i>
                Modifier mes informations
            </h3>
        </div>
        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')

            <!-- Avatar Upload -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Photo de profil</label>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                        @if($user->avatar)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-16 h-16 object-cover">
                        @else
                            <span class="text-2xl font-bold text-gray-400">{{ $user->initial }}</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" name="avatar" accept="image/*" id="avatar-input"
                               class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG ou GIF. Max 2MB.</p>
                    </div>
                </div>
                @error('avatar')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('first_name') border-red-500 @enderror">
                    @error('first_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('last_name') border-red-500 @enderror">
                    @error('last_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">@</span>
                        <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}"
                               class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('username') border-red-500 @enderror">
                    </div>
                    @error('username')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" placeholder="+229 XX XX XX XX"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Bio -->
            <div class="mt-6">
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                <textarea name="bio" id="bio" rows="3" maxlength="500" placeholder="Une courte description de vous..."
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('bio') border-red-500 @enderror">{{ old('bio', $user->bio) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Max 500 caractères</p>
                @error('bio')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    <!-- Change Password Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-lock mr-2 text-primary-500"></i>
                Changer mon mot de passe
            </h3>
        </div>
        <form action="{{ route('admin.profile.password') }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                    <input type="password" name="current_password" id="current_password"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                    <input type="password" name="password" id="password"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
            </div>

            <p class="text-sm text-gray-500 mt-3">Le mot de passe doit contenir au moins 8 caractères.</p>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg transition-colors flex items-center">
                    <i class="fas fa-key mr-2"></i>
                    Mettre à jour le mot de passe
                </button>
            </div>
        </form>
    </div>

    <!-- Account Info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-info-circle mr-2 text-primary-500"></i>
                Informations du compte
            </h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">ID du compte</dt>
                    <dd class="font-medium text-gray-800">#{{ $user->id }}</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">Rôle</dt>
                    <dd class="font-medium text-gray-800">{{ $user->role_label }}</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">Date d'inscription</dt>
                    <dd class="font-medium text-gray-800">{{ $user->created_at->format('d/m/Y à H:i') }}</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">Dernière connexion</dt>
                    <dd class="font-medium text-gray-800">{{ $user->last_seen_at ? $user->last_seen_at->format('d/m/Y à H:i') : 'N/A' }}</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">Email vérifié</dt>
                    <dd>
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>
                                Oui ({{ $user->email_verified_at->format('d/m/Y') }})
                            </span>
                        @else
                            <span class="inline-flex items-center text-red-600">
                                <i class="fas fa-times-circle mr-1"></i>
                                Non
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500">Statut du compte</dt>
                    <dd>
                        @if($user->is_banned)
                            <span class="inline-flex items-center text-red-600">
                                <i class="fas fa-ban mr-1"></i>
                                Banni
                            </span>
                        @else
                            <span class="inline-flex items-center text-green-600">
                                <i class="fas fa-check-circle mr-1"></i>
                                Actif
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
