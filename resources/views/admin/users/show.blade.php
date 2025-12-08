@extends('admin.layouts.app')

@section('title', 'Utilisateur - ' . $user->username)
@section('header', 'Profil utilisateur')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('admin.users.index') }}" class="text-primary-600 hover:text-primary-700">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
    </a>
    <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <i class="fas fa-edit mr-2"></i>Modifier
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-8 text-center">
                <div class="w-24 h-24 bg-white rounded-full mx-auto flex items-center justify-center shadow-lg overflow-hidden">
                    @if($user->avatar)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->username }}" class="w-24 h-24 object-cover">
                    @else
                        <span class="text-4xl font-bold text-primary-600">{{ $user->initial }}</span>
                    @endif
                </div>
                <h2 class="text-xl font-bold text-white mt-4">{{ $user->full_name }}</h2>
                <p class="text-primary-100">{{ '@' . $user->username }}</p>
                @if($user->is_online)
                    <span class="inline-flex items-center mt-2 px-2 py-1 bg-green-400 text-green-900 rounded-full text-xs">
                        <span class="w-2 h-2 bg-green-600 rounded-full mr-1 animate-pulse"></span>
                        En ligne
                    </span>
                @endif
            </div>

            <!-- Info -->
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Rôle</span>
                    @php
                        $roleColors = [
                            'admin' => 'bg-red-100 text-red-700',
                            'moderator' => 'bg-blue-100 text-blue-700',
                            'user' => 'bg-gray-100 text-gray-700',
                        ];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $roleColors[$user->role] ?? $roleColors['user'] }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Statut</span>
                    @if($user->is_banned)
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">Banni</span>
                    @elseif($user->is_verified)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Vérifié</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">Non vérifié</span>
                    @endif
                </div>

                <div class="border-t border-gray-200 pt-4 space-y-3">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-envelope w-5 mr-3 text-gray-400"></i>
                        <span class="text-sm truncate">{{ $user->email }}</span>
                        @if($user->email_verified_at)
                            <i class="fas fa-check-circle text-green-500 ml-2" title="Email vérifié"></i>
                        @endif
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-phone w-5 mr-3 text-gray-400"></i>
                        <span class="text-sm">{{ $user->phone ?? 'Non renseigné' }}</span>
                        @if($user->phone_verified_at)
                            <i class="fas fa-check-circle text-green-500 ml-2" title="Téléphone vérifié"></i>
                        @endif
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-calendar w-5 mr-3 text-gray-400"></i>
                        <span class="text-sm">Inscrit le {{ $user->created_at->format('d/m/Y à H:i') }}</span>
                    </div>
                    @if($user->last_seen_at)
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-clock w-5 mr-3 text-gray-400"></i>
                        <span class="text-sm">Dernière activité {{ $user->last_seen_at->diffForHumans() }}</span>
                    </div>
                    @endif
                </div>

                @if($user->bio)
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm text-gray-500 mb-1">Bio</p>
                    <p class="text-sm text-gray-700">{{ $user->bio }}</p>
                </div>
                @endif

                <!-- Actions -->
                <div class="border-t border-gray-200 pt-4 space-y-2">
                    @if(!$user->is_banned)
                        <button onclick="document.getElementById('banModal').classList.remove('hidden')"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-ban mr-2"></i>Bannir l'utilisateur
                        </button>
                    @else
                        <form action="{{ route('admin.users.unban', $user) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-check-circle mr-2"></i>Débannir l'utilisateur
                            </button>
                        </form>
                        @if($user->banned_reason)
                        <div class="mt-3 p-3 bg-red-50 rounded-lg">
                            <p class="text-xs text-red-600 font-medium">Raison du bannissement:</p>
                            <p class="text-sm text-red-700">{{ $user->banned_reason }}</p>
                            <p class="text-xs text-red-500 mt-1">Banni le {{ $user->banned_at?->format('d/m/Y à H:i') }}</p>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stats & Activity -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-primary-600">{{ number_format($userStats['messages_received'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Messages reçus</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-blue-600">{{ number_format($userStats['messages_sent'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Messages envoyés</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-pink-600">{{ number_format($userStats['confessions_written'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Confessions écrites</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                <p class="text-3xl font-bold text-purple-600">{{ number_format($userStats['confessions_received'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Confessions reçues</p>
            </div>
        </div>

        <!-- Wallet Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-wallet text-green-500 mr-2"></i>Portefeuille
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Solde actuel</p>
                    <p class="text-xl font-bold text-gray-900">{{ number_format($userStats['wallet_balance'] ?? 0) }} FCFA</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total retiré</p>
                    <p class="text-xl font-bold text-blue-600">{{ number_format($userStats['total_withdrawn'] ?? 0) }} FCFA</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Cadeaux reçus</p>
                    <p class="text-xl font-bold text-pink-600">{{ $userStats['gifts_received'] ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Cadeaux envoyés</p>
                    <p class="text-xl font-bold text-purple-600">{{ $userStats['gifts_sent'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Activité récente</h3>
            </div>
            <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                @forelse($recentActivity ?? [] as $activity)
                    <div class="px-6 py-4 flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                            @if($activity['type'] == 'message') bg-purple-100 text-purple-600
                            @elseif($activity['type'] == 'confession') bg-pink-100 text-pink-600
                            @elseif($activity['type'] == 'gift') bg-yellow-100 text-yellow-600
                            @else bg-gray-100 text-gray-600 @endif">
                            <i class="fas {{ $activity['icon'] ?? 'fa-circle' }}"></i>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-sm text-gray-900">{{ $activity['description'] ?? '' }}</p>
                            <p class="text-xs text-gray-500">{{ $activity['time'] ?? '' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-history text-3xl mb-2"></i>
                        <p>Aucune activité récente</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Reports against this user -->
        @if(isset($reports) && count($reports) > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                    <h3 class="text-lg font-semibold text-red-800">
                        <i class="fas fa-flag mr-2"></i>Signalements ({{ count($reports) }})
                    </h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($reports as $report)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">{{ $report->reason }}</span>
                                <span class="text-xs text-gray-500">{{ $report->created_at->diffForHumans() }}</span>
                            </div>
                            @if($report->description)
                            <p class="text-sm text-gray-600">{{ $report->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
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
