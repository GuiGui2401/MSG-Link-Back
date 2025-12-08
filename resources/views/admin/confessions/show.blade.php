@extends('admin.layouts.app')

@section('title', 'Confession #' . $confession->id)
@section('header', 'Détails de la confession')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.confessions.index') }}" class="text-primary-600 hover:text-primary-700">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Confession Content -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-gray-500">#{{ $confession->id }}</span>
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
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$confession->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $statusLabels[$confession->status] ?? $confession->status }}
                    </span>
                </div>
                <span class="text-sm text-gray-500">{{ $confession->created_at->format('d/m/Y à H:i') }}</span>
            </div>

            <div class="p-6">
                <p class="text-gray-800 whitespace-pre-wrap text-lg leading-relaxed">{{ $confession->content }}</p>
            </div>

            @if($confession->status === 'pending')
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                <form action="{{ route('admin.confessions.reject', $confession) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>Rejeter
                    </button>
                </form>
                <form action="{{ route('admin.confessions.approve', $confession) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-check mr-2"></i>Approuver
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- Engagement Stats -->
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Engagement</h3>
            <div class="grid grid-cols-3 gap-4">
                <div class="text-center">
                    <p class="text-3xl font-bold text-pink-600">{{ $confession->likes_count ?? 0 }}</p>
                    <p class="text-sm text-gray-500">J'aime</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-blue-600">{{ $confession->comments_count ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Commentaires</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-purple-600">{{ $confession->views_count ?? 0 }}</p>
                    <p class="text-sm text-gray-500">Vues</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Info -->
    <div class="space-y-6">
        <!-- Author Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Auteur</h3>
            @if($confession->author)
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                        @if($confession->author->avatar)
                            <img src="{{ $confession->author->avatar_url }}" alt="" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <span class="text-xl font-bold text-primary-600">{{ $confession->author->initial }}</span>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('admin.users.show', $confession->author) }}" class="font-medium text-gray-900 hover:text-primary-600">
                            {{ $confession->author->full_name }}
                        </a>
                        <p class="text-sm text-gray-500">{{ '@' . $confession->author->username }}</p>
                    </div>
                </div>
            @else
                <p class="text-gray-500">Auteur anonyme</p>
            @endif
        </div>

        <!-- Recipient Info -->
        @if($confession->recipient)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Destinataire</h3>
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    @if($confession->recipient->avatar)
                        <img src="{{ $confession->recipient->avatar_url }}" alt="" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <span class="text-xl font-bold text-blue-600">{{ $confession->recipient->initial }}</span>
                    @endif
                </div>
                <div>
                    <a href="{{ route('admin.users.show', $confession->recipient) }}" class="font-medium text-gray-900 hover:text-primary-600">
                        {{ $confession->recipient->full_name }}
                    </a>
                    <p class="text-sm text-gray-500">{{ '@' . $confession->recipient->username }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Moderation Info -->
        @if($confession->moderated_at)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Modération</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Statut:</span>
                    <span class="font-medium">{{ $statusLabels[$confession->status] ?? $confession->status }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Modéré le:</span>
                    <span class="font-medium">{{ $confession->moderated_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($confession->moderator)
                <div class="flex justify-between">
                    <span class="text-gray-500">Par:</span>
                    <span class="font-medium">{{ $confession->moderator->full_name ?? 'N/A' }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
            <div class="space-y-2">
                <form action="{{ route('admin.confessions.destroy', $confession) }}" method="POST"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette confession ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Supprimer la confession
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
