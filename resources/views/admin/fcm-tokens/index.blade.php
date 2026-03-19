@extends('admin.layouts.app')

@section('title', 'FCM Tokens')
@section('header', 'Gestion des FCM Tokens')

@section('content')
<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes progress {
        from {
            width: 0;
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out;
    }

    .animate-slideIn {
        animation: slideIn 0.6s ease-out;
    }

    .animate-scaleIn {
        animation: scaleIn 0.5s ease-out;
    }

    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }

    .table-row {
        transition: all 0.2s ease;
    }

    .table-row:hover {
        background: linear-gradient(to right, #f9fafb, #ffffff);
        transform: translateX(5px);
    }

    .progress-bar {
        animation: progress 1.5s ease-out;
    }

    .badge-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: .7;
        }
    }
</style>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Users -->
    <div class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl shadow-lg border border-blue-200 p-6 animate-fadeInUp" style="animation-delay: 0.1s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-1">Total utilisateurs</p>
                <p class="text-4xl font-bold text-blue-900">{{ number_format($stats['total_users'] ?? 0) }}</p>
            </div>
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-users text-2xl text-white"></i>
            </div>
        </div>
    </div>

    <!-- With FCM -->
    <div class="stat-card bg-gradient-to-br from-green-50 to-emerald-100 rounded-2xl shadow-lg border border-green-200 p-6 animate-fadeInUp" style="animation-delay: 0.2s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-green-600 uppercase tracking-wide mb-1">Avec FCM Token</p>
                <p class="text-4xl font-bold text-green-900">{{ number_format($stats['with_fcm'] ?? 0) }}</p>
            </div>
            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-bell text-2xl text-white"></i>
            </div>
        </div>
    </div>

    <!-- Without FCM -->
    <div class="stat-card bg-gradient-to-br from-red-50 to-rose-100 rounded-2xl shadow-lg border border-red-200 p-6 animate-fadeInUp" style="animation-delay: 0.3s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-red-600 uppercase tracking-wide mb-1">Sans FCM Token</p>
                <p class="text-4xl font-bold text-red-900">{{ number_format($stats['without_fcm'] ?? 0) }}</p>
            </div>
            <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-bell-slash text-2xl text-white"></i>
            </div>
        </div>
    </div>

    <!-- Coverage -->
    <div class="stat-card bg-gradient-to-br from-purple-50 to-violet-100 rounded-2xl shadow-lg border border-purple-200 p-6 animate-fadeInUp" style="animation-delay: 0.4s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-purple-600 uppercase tracking-wide mb-1">Couverture FCM</p>
                <p class="text-4xl font-bold text-purple-900">{{ number_format($stats['percentage_with_fcm'] ?? 0, 1) }}%</p>
            </div>
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-chart-pie text-2xl text-white"></i>
            </div>
        </div>
    </div>
</div>

<!-- Info Banner -->
<div class="mb-6 animate-slideIn" style="animation-delay: 0.5s">
    <div class="bg-gradient-to-r from-blue-500 via-blue-600 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-info-circle text-2xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold mb-2">À propos des FCM Tokens</h3>
                <p class="text-blue-100 mb-3">Les tokens FCM (Firebase Cloud Messaging) permettent d'envoyer des notifications push aux utilisateurs.</p>
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <i class="fas fa-check-circle text-green-300 mr-2"></i>
                        <span class="text-sm">Avec token = Notif push actives</span>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <i class="fas fa-times-circle text-red-300 mr-2"></i>
                        <span class="text-sm">Sans token = Pas de notif</span>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <i class="fas fa-mobile-alt text-yellow-300 mr-2"></i>
                        <span class="text-sm">Envoi auto au login/register</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6 animate-scaleIn" style="animation-delay: 0.6s">
    <form action="{{ route('admin.fcm-tokens.index') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[250px]">
            <div class="relative">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Rechercher par nom, email, username..."
                       class="w-full px-5 py-3 pl-12 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
        <div class="w-60">
            <select name="fcm_status" class="w-full px-5 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300">
                <option value="">📊 Tous les statuts</option>
                <option value="with" {{ request('fcm_status') == 'with' ? 'selected' : '' }}>✅ Avec FCM Token</option>
                <option value="without" {{ request('fcm_status') == 'without' ? 'selected' : '' }}>❌ Sans FCM Token</option>
            </select>
        </div>
        <button type="submit" class="px-8 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300">
            <i class="fas fa-search mr-2"></i>Filtrer
        </button>
        @if(request()->hasAny(['search', 'fcm_status']))
            <a href="{{ route('admin.fcm-tokens.index') }}" class="px-8 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 hover:shadow-lg transition-all duration-300">
                <i class="fas fa-times mr-2"></i>Réinitialiser
            </a>
        @endif
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden animate-fadeInUp" style="animation-delay: 0.7s">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <i class="fas fa-user mr-2 text-primary-500"></i>Utilisateur
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <i class="fas fa-envelope mr-2 text-primary-500"></i>Email
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <i class="fas fa-at mr-2 text-primary-500"></i>Username
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <i class="fas fa-signal mr-2 text-primary-500"></i>Statut FCM
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <i class="fas fa-key mr-2 text-primary-500"></i>FCM Token
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <i class="fas fa-calendar mr-2 text-primary-500"></i>Inscription
                    </th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <i class="fas fa-cog mr-2 text-primary-500"></i>Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="table-row">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                @if($user->avatar)
                                    <img class="h-12 w-12 rounded-xl object-cover ring-2 ring-primary-100" src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->first_name }}">
                                @else
                                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center ring-2 ring-primary-100 shadow-lg">
                                        <span class="text-white font-bold text-lg">{{ substr($user->first_name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </div>
                                    @if($user->is_verified)
                                        <span class="text-xs text-blue-600"><i class="fas fa-check-circle mr-1"></i>Vérifié</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono text-primary-600 font-semibold">{{ '@' . $user->username }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->fcm_token)
                                <span class="px-4 py-2 inline-flex items-center text-xs leading-5 font-bold rounded-xl bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200 shadow-sm">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                    Actif
                                </span>
                            @else
                                <span class="px-4 py-2 inline-flex items-center text-xs leading-5 font-bold rounded-xl bg-gradient-to-r from-red-100 to-rose-100 text-red-800 border border-red-200 shadow-sm">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            @if($user->fcm_token)
                                <div class="group relative">
                                    <div class="text-xs text-gray-500 font-mono truncate bg-gray-50 px-3 py-2 rounded-lg border border-gray-200">
                                        {{ substr($user->fcm_token, 0, 35) }}...
                                    </div>
                                    <div class="hidden group-hover:block absolute z-10 w-96 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-2xl -top-2 left-0 transform -translate-y-full">
                                        <div class="font-mono break-all">{{ $user->fcm_token }}</div>
                                        <div class="absolute bottom-0 left-8 transform translate-y-1/2 rotate-45 w-2 h-2 bg-gray-900"></div>
                                    </div>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Aucun token</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $user->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-500">
                                <i class="far fa-clock mr-1"></i>{{ $user->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <a href="{{ route('admin.users.show', $user) }}"
                               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-purple-600 text-white text-sm font-semibold rounded-lg hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                                <i class="fas fa-eye mr-2"></i>Voir
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-users text-gray-400 text-4xl"></i>
                                </div>
                                <p class="text-gray-500 text-lg font-semibold mb-2">Aucun utilisateur trouvé</p>
                                @if(request()->hasAny(['search', 'fcm_status']))
                                    <a href="{{ route('admin.fcm-tokens.index') }}" class="mt-3 text-primary-600 hover:text-primary-800 font-medium">
                                        <i class="fas fa-redo mr-2"></i>Réinitialiser les filtres
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    @endif
</div>

<!-- Statistics Summary -->
<div class="mt-8 bg-gradient-to-br from-white to-gray-50 rounded-2xl shadow-xl border border-gray-100 p-8 animate-fadeInUp" style="animation-delay: 0.8s">
    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
        <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-purple-600 rounded-xl flex items-center justify-center mr-3">
            <i class="fas fa-chart-bar text-white"></i>
        </div>
        Résumé des statistiques
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100 shadow-sm hover:shadow-lg transition-shadow duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-green-600 uppercase mb-1">Utilisateurs avec notifications</p>
                    <p class="text-3xl font-bold text-green-900">{{ number_format($stats['with_fcm'] ?? 0) }}</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-bell text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-xl p-6 border border-red-100 shadow-sm hover:shadow-lg transition-shadow duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-red-600 uppercase mb-1">Utilisateurs sans notifications</p>
                    <p class="text-3xl font-bold text-red-900">{{ number_format($stats['without_fcm'] ?? 0) }}</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-red-400 to-rose-500 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-bell-slash text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mt-6">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-bold text-gray-700 flex items-center">
                <i class="fas fa-chart-line text-primary-600 mr-2"></i>
                Couverture FCM
            </span>
            <span class="text-lg font-bold text-primary-600">{{ number_format($stats['percentage_with_fcm'] ?? 0, 2) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-6 shadow-inner overflow-hidden">
            <div class="progress-bar bg-gradient-to-r from-green-500 via-emerald-500 to-green-600 h-6 rounded-full flex items-center justify-end pr-3 shadow-lg transition-all duration-1000"
                 style="width: {{ $stats['percentage_with_fcm'] ?? 0 }}%">
                @if(($stats['percentage_with_fcm'] ?? 0) > 10)
                    <span class="text-xs font-bold text-white">{{ number_format($stats['percentage_with_fcm'] ?? 0, 1) }}%</span>
                @endif
            </div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-gray-500">
            <span><i class="fas fa-arrow-left mr-1"></i>0%</span>
            <span>100%<i class="fas fa-arrow-right ml-1"></i></span>
        </div>
    </div>
</div>
@endsection
