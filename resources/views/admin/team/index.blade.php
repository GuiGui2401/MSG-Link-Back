@extends('admin.layouts.app')

@section('title', 'Équipe')
@section('header', 'Gestion de l\'équipe')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-crown text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Super Admins</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['superadmins'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Administrateurs</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['admins'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-check text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Modérateurs</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['moderators'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Header with action -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-lg font-semibold text-gray-800">Membres de l'équipe</h2>
    <a href="{{ route('admin.team.create') }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>Ajouter un membre
    </a>
</div>

<!-- Team Members Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscrit le</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($teamMembers as $member)
                    <tr class="hover:bg-gray-50 {{ $member->id === auth()->id() ? 'bg-primary-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    @if($member->avatar)
                                        <img src="{{ $member->avatar_url }}" alt="" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <span class="text-lg font-bold text-primary-600">{{ $member->initial }}</span>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $member->full_name }}
                                        @if($member->id === auth()->id())
                                            <span class="text-xs text-primary-600 ml-1">(vous)</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">{{ '@' . $member->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $member->email }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $roleColors = [
                                    'superadmin' => 'bg-purple-100 text-purple-700',
                                    'admin' => 'bg-blue-100 text-blue-700',
                                    'moderator' => 'bg-green-100 text-green-700',
                                ];
                                $roleIcons = [
                                    'superadmin' => 'fa-crown',
                                    'admin' => 'fa-user-shield',
                                    'moderator' => 'fa-user-check',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleColors[$member->role] ?? 'bg-gray-100 text-gray-700' }}">
                                <i class="fas {{ $roleIcons[$member->role] ?? 'fa-user' }} mr-1"></i>
                                {{ $member->role_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($member->is_banned)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Banni</span>
                            @elseif($member->is_online)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">En ligne</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Hors ligne</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $member->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.users.show', $member) }}" class="text-primary-600 hover:text-primary-700 mr-3" title="Voir le profil">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(auth()->user()->canManage($member) || $member->id === auth()->id())
                                <a href="{{ route('admin.users.edit', $member) }}" class="text-blue-600 hover:text-blue-700" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-4"></i>
                            <p>Aucun membre d'équipe trouvé</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Permissions Info -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
    <h3 class="text-sm font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Hiérarchie des rôles</h3>
    <ul class="text-sm text-blue-700 space-y-1">
        <li><strong>Super Admin</strong> : Accès total, peut gérer tous les utilisateurs y compris les admins</li>
        <li><strong>Administrateur</strong> : Peut gérer les modérateurs et utilisateurs, mais pas les autres admins</li>
        <li><strong>Modérateur</strong> : Peut modérer le contenu et gérer les utilisateurs standards</li>
    </ul>
</div>
@endsection
