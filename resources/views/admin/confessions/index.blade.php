@extends('admin.layouts.app')

@section('title', 'Confessions')
@section('header', 'Gestion des confessions')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center">
                <i class="fas fa-heart text-pink-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">En attente</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-green-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['approved'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Approuvées</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-times text-red-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['rejected'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">Rejetées</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form action="{{ route('admin.confessions.index') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Rechercher dans le contenu..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div class="w-40">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Tous les statuts</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approuvée</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejetée</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-search mr-2"></i>Filtrer
        </button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.confessions.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-times mr-2"></i>Réinitialiser
            </a>
        @endif
    </form>
</div>

<!-- Confessions Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($confessions as $confession)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center text-pink-600">
                            <i class="fas fa-heart text-sm"></i>
                        </div>
                        <div class="ml-2">
                            @if($confession->is_anonymous)
                                <p class="text-sm font-medium text-gray-900">Anonyme</p>
                            @else
                                <p class="text-sm font-medium text-gray-900">{{ $confession->author->first_name ?? 'Utilisateur' }}</p>
                                <p class="text-xs text-gray-500">{{ '@' . ($confession->author->username ?? 'unknown') }}</p>
                            @endif
                        </div>
                    </div>
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'approved' => 'bg-green-100 text-green-700',
                            'rejected' => 'bg-red-100 text-red-700',
                        ];
                        $statusLabels = [
                            'pending' => 'En attente',
                            'approved' => 'Approuvée',
                            'rejected' => 'Rejetée',
                        ];
                    @endphp
                    <span class="px-2 py-1 {{ $statusColors[$confession->status] ?? 'bg-gray-100 text-gray-700' }} rounded-full text-xs font-medium">
                        {{ $statusLabels[$confession->status] ?? $confession->status }}
                    </span>
                </div>
            </div>

            <div class="p-4">
                <p class="text-gray-700 text-sm leading-relaxed">{{ Str::limit($confession->content, 200) }}</p>

                @if($confession->recipient)
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-at mr-1"></i>
                            Destiné à {{ '@' . $confession->recipient->username }}
                        </p>
                    </div>
                @endif

                <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                    <span>{{ $confession->created_at->diffForHumans() }}</span>
                    <div class="flex items-center space-x-3">
                        <span><i class="fas fa-heart text-pink-400 mr-1"></i>{{ $confession->likes_count ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <div class="flex space-x-2">
                    @if($confession->status == 'pending')
                        <form action="{{ route('admin.confessions.approve', $confession) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs hover:bg-green-700 transition-colors">
                                <i class="fas fa-check mr-1"></i>Approuver
                            </button>
                        </form>
                        <form action="{{ route('admin.confessions.reject', $confession) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 rounded text-xs hover:bg-red-200 transition-colors">
                                <i class="fas fa-times mr-1"></i>Rejeter
                            </button>
                        </form>
                    @endif
                </div>
                <form action="{{ route('admin.confessions.destroy', $confession) }}" method="POST"
                      onsubmit="return confirm('Supprimer définitivement cette confession ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-12 text-gray-500">
            <i class="fas fa-heart text-4xl mb-3 text-pink-300"></i>
            <p>Aucune confession trouvée</p>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($confessions->hasPages())
    <div class="mt-6">
        {{ $confessions->links() }}
    </div>
@endif
@endsection
