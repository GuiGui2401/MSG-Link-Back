@extends('admin.layouts.app')

@section('title', 'Détails du signalement')
@section('header', 'Détails du signalement')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.moderation.index') }}" class="text-primary-600 hover:text-primary-700">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la modération
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Report Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Report Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Informations du signalement</h3>
                @if($report->status == 'pending')
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">En attente</span>
                @elseif($report->status == 'resolved')
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Résolu</span>
                @else
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">Rejeté</span>
                @endif
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Type de contenu</p>
                        <p class="font-medium text-gray-900">{{ ucfirst(class_basename($report->reportable_type)) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Raison</p>
                        <p class="font-medium text-gray-900">{{ $report->reason }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Date du signalement</p>
                        <p class="font-medium text-gray-900">{{ $report->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Signalé par</p>
                        <a href="{{ route('admin.users.show', $report->reporter) }}" class="font-medium text-primary-600 hover:text-primary-700">
                            {{ '@' . ($report->reporter->username ?? 'unknown') }}
                        </a>
                    </div>
                </div>

                @if($report->description)
                    <div class="border-t border-gray-200 pt-4">
                        <p class="text-sm text-gray-500 mb-2">Description</p>
                        <p class="text-gray-700">{{ $report->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Reported Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h3 class="text-lg font-semibold text-red-800">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Contenu signalé
                </h3>
            </div>
            <div class="p-6">
                @if($report->reportable)
                    @if($report->reportable_type == 'App\Models\AnonymousMessage')
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-700">{{ $report->reportable->content }}</p>
                            <div class="mt-3 pt-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-500">
                                <span>Envoyé le {{ $report->reportable->created_at->format('d/m/Y à H:i') }}</span>
                                <span>À {{ '@' . ($report->reportable->recipient->username ?? 'unknown') }}</span>
                            </div>
                        </div>
                    @elseif($report->reportable_type == 'App\Models\Confession')
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-700">{{ $report->reportable->content }}</p>
                            <div class="mt-3 pt-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-500">
                                <span>Publié le {{ $report->reportable->created_at->format('d/m/Y à H:i') }}</span>
                                @if(!$report->reportable->is_anonymous)
                                    <span>Par {{ '@' . ($report->reportable->author->username ?? 'unknown') }}</span>
                                @else
                                    <span class="italic">Anonyme</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500">Contenu non disponible pour ce type</p>
                    @endif
                @else
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-trash-alt text-4xl mb-3"></i>
                        <p>Le contenu signalé a été supprimé</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        @if($report->status == 'pending')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
                <div class="space-y-3">
                    <form action="{{ route('admin.moderation.resolve', $report) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check mr-2"></i>Marquer comme résolu
                        </button>
                    </form>

                    <form action="{{ route('admin.moderation.dismiss', $report) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fas fa-times mr-2"></i>Rejeter le signalement
                        </button>
                    </form>

                    @if($report->reportable)
                        <form action="{{ route('admin.moderation.delete-content', $report) }}" method="POST"
                              onsubmit="return confirm('Supprimer définitivement ce contenu ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash mr-2"></i>Supprimer le contenu
                            </button>
                        </form>

                        @if($report->reportable->sender ?? $report->reportable->author ?? null)
                            @php
                                $contentOwner = $report->reportable->sender ?? $report->reportable->author;
                            @endphp
                            @if(!$contentOwner->is_banned)
                                <form action="{{ route('admin.moderation.resolve-and-ban', $report) }}" method="POST"
                                      onsubmit="return confirm('Résoudre et bannir l\'auteur ?')">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-red-800 text-white rounded-lg hover:bg-red-900 transition-colors">
                                        <i class="fas fa-user-slash mr-2"></i>Résoudre et bannir l'auteur
                                    </button>
                                </form>
                            @endif
                        @endif
                    @endif
                </div>
            </div>
        @endif

        <!-- Reporter Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Signalé par</h3>
            <div class="flex items-center">
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold">
                    {{ strtoupper(substr($report->reporter->first_name ?? 'U', 0, 1)) }}
                </div>
                <div class="ml-3">
                    <p class="font-medium text-gray-900">{{ $report->reporter->first_name ?? 'Utilisateur' }} {{ $report->reporter->last_name ?? '' }}</p>
                    <p class="text-sm text-gray-500">{{ '@' . ($report->reporter->username ?? 'unknown') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.users.show', $report->reporter) }}"
               class="mt-4 block text-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Voir le profil
            </a>
        </div>

        <!-- Content Owner Info -->
        @if($report->reportable && ($report->reportable->sender ?? $report->reportable->author ?? null))
            @php
                $owner = $report->reportable->sender ?? $report->reportable->author;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Auteur du contenu</h3>
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600 font-bold">
                        {{ strtoupper(substr($owner->first_name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-900">{{ $owner->first_name ?? 'Utilisateur' }} {{ $owner->last_name ?? '' }}</p>
                        <p class="text-sm text-gray-500">{{ '@' . ($owner->username ?? 'unknown') }}</p>
                    </div>
                </div>
                @if($owner->is_banned)
                    <div class="mt-4 px-3 py-2 bg-red-100 rounded-lg text-red-700 text-sm text-center">
                        <i class="fas fa-ban mr-1"></i>Utilisateur banni
                    </div>
                @endif
                <a href="{{ route('admin.users.show', $owner) }}"
                   class="mt-4 block text-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Voir le profil
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
