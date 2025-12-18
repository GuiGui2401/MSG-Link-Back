@extends('admin.layouts.app')

@section('title', 'Stories')
@section('header', 'Gestion des Stories')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-images text-xl text-indigo-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Actives</p>
                <p class="text-3xl font-bold text-green-600 mt-1">{{ number_format($stats['active']) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-xl text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Expirées</p>
                <p class="text-3xl font-bold text-gray-600 mt-1">{{ number_format($stats['expired']) }}</p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-xl text-gray-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Aujourd'hui</p>
                <p class="text-3xl font-bold text-blue-600 mt-1">{{ number_format($stats['today']) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-day text-xl text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total vues</p>
                <p class="text-3xl font-bold text-purple-600 mt-1">{{ number_format($stats['total_views']) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-eye text-xl text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <form method="GET" action="{{ route('admin.stories.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Nom d'utilisateur..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">Tous</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actives</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expirées</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
            <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">Tous</option>
                <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Image</option>
                <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Vidéo</option>
                <option value="text" {{ request('type') === 'text' ? 'selected' : '' }}>Texte</option>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-search mr-2"></i>Filtrer
            </button>
            @if(request()->hasAny(['search', 'status', 'type']))
                <a href="{{ route('admin.stories.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Stories List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contenu</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vues</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créée</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($stories as $story)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 font-semibold flex-shrink-0">
                                    {{ strtoupper(substr($story->user->first_name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $story->user->first_name }} {{ $story->user->last_name }}</p>
                                    <p class="text-sm text-gray-500">{{ '@' . $story->user->username }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($story->type === 'image')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-image mr-1"></i>Image
                                </span>
                            @elseif($story->type === 'video')
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-video mr-1"></i>Vidéo
                                </span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-font mr-1"></i>Texte
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($story->type === 'text')
                                <p class="text-sm text-gray-900 truncate max-w-xs">{{ $story->content }}</p>
                            @else
                                <a href="{{ asset('storage/' . $story->media_url) }}" target="_blank" class="text-sm text-primary-600 hover:text-primary-700">
                                    <i class="fas fa-external-link-alt mr-1"></i>Voir le média
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center text-sm text-gray-900">
                                <i class="fas fa-eye text-gray-400 mr-2"></i>
                                {{ number_format($story->views_count) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($story->expires_at->isFuture() && $story->status === 'active')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Active</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">Expirée</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $story->expires_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $story->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <form action="{{ route('admin.stories.destroy', $story) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette story ?')"
                                        class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i class="fas fa-images text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Aucune story trouvée</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($stories->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $stories->links() }}
        </div>
    @endif
</div>
@endsection
