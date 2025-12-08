@extends('admin.layouts.app')

@section('title', 'Utilisateurs')
@section('header', 'Gestion des utilisateurs')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-check text-green-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['active'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Actifs</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-slash text-red-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['banned'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Bannis</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-plus text-purple-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['today'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Aujourd'hui</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form action="{{ route('admin.users.index') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Rechercher par nom, email, username..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div class="w-40">
            <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Tous les rôles</option>
                <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Utilisateur</option>
                <option value="moderator" {{ request('role') == 'moderator' ? 'selected' : '' }}>Modérateur</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
        <div class="w-40">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Tous les statuts</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                <option value="banned" {{ request('status') == 'banned' ? 'selected' : '' }}>Banni</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-search mr-2"></i>Filtrer
        </button>
        @if(request()->hasAny(['search', 'role', 'status']))
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-times mr-2"></i>Réinitialiser
            </a>
        @endif
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Inscription</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold">
                                    @if($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        {{ strtoupper(substr($user->first_name ?? 'U', 0, 1)) }}
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <p class="font-medium text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                                    <p class="text-sm text-gray-500">{{ '@' . $user->username }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900">{{ $user->email }}</p>
                            <p class="text-sm text-gray-500">{{ $user->phone ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $roleColors = [
                                    'admin' => 'bg-red-100 text-red-700',
                                    'moderator' => 'bg-blue-100 text-blue-700',
                                    'user' => 'bg-gray-100 text-gray-700',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $roleColors[$user->role] ?? $roleColors['user'] }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_banned)
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Banni</span>
                            @elseif($user->is_verified)
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Vérifié</span>
                            @else
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Non vérifié</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900">{{ $user->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $user->created_at->format('H:i') }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('admin.users.show', $user) }}"
                                   class="p-2 text-gray-400 hover:text-primary-600 transition-colors"
                                   title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(!$user->is_banned)
                                    <form action="{{ route('admin.users.ban', $user) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir bannir cet utilisateur ?')">
                                        @csrf
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Bannir">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.users.unban', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 text-gray-400 hover:text-green-600 transition-colors" title="Débannir">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-3"></i>
                            <p>Aucun utilisateur trouvé</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
