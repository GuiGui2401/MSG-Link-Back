@extends('admin.layouts.app')

@section('title', 'Détails du retrait')
@section('header', 'Détails du retrait #' . $withdrawal->id)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.withdrawals.index') }}" class="text-primary-600 hover:text-primary-700">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Withdrawal Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Informations du retrait</h3>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'completed' => 'bg-green-100 text-green-700',
                        'rejected' => 'bg-red-100 text-red-700',
                        'failed' => 'bg-red-100 text-red-700',
                    ];
                    $statusLabels = [
                        'pending' => 'En attente',
                        'processing' => 'En cours',
                        'completed' => 'Complété',
                        'rejected' => 'Rejeté',
                        'failed' => 'Échoué',
                    ];
                @endphp
                <span class="px-3 py-1 {{ $statusColors[$withdrawal->status] ?? 'bg-gray-100 text-gray-700' }} rounded-full text-sm font-medium">
                    {{ $statusLabels[$withdrawal->status] ?? $withdrawal->status }}
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Montant demandé</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($withdrawal->amount) }} FCFA</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Montant net (après frais)</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($withdrawal->net_amount) }} FCFA</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Frais</p>
                        <p class="font-medium text-gray-900">{{ number_format($withdrawal->fee ?? 0) }} FCFA</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Méthode</p>
                        <p class="font-medium text-gray-900">{{ strtoupper(str_replace('_', ' ', $withdrawal->method)) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Numéro de téléphone</p>
                        <p class="font-medium text-gray-900 font-mono">{{ $withdrawal->phone_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Date de demande</p>
                        <p class="font-medium text-gray-900">{{ $withdrawal->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                </div>

                @if($withdrawal->transaction_reference)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-500 mb-1">Référence de transaction</p>
                        <p class="font-medium text-gray-900 font-mono bg-gray-100 px-3 py-2 rounded">{{ $withdrawal->transaction_reference }}</p>
                    </div>
                @endif

                @if($withdrawal->processed_at)
                    <div class="mt-4">
                        <p class="text-sm text-gray-500 mb-1">Traité le</p>
                        <p class="font-medium text-gray-900">{{ $withdrawal->processed_at->format('d/m/Y à H:i') }}</p>
                    </div>
                @endif

                @if($withdrawal->rejection_reason)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="bg-red-50 rounded-lg p-4">
                            <p class="text-sm font-medium text-red-800 mb-1">Raison du rejet</p>
                            <p class="text-red-700">{{ $withdrawal->rejection_reason }}</p>
                        </div>
                    </div>
                @endif

                @if($withdrawal->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-sm text-gray-500 mb-1">Notes</p>
                        <p class="text-gray-700">{{ $withdrawal->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Historique</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-plus text-blue-600 text-sm"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Demande créée</p>
                            <p class="text-sm text-gray-500">{{ $withdrawal->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>

                    @if($withdrawal->processed_at && $withdrawal->status == 'completed')
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check text-green-600 text-sm"></i>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">Paiement effectué</p>
                                <p class="text-sm text-gray-500">{{ $withdrawal->processed_at->format('d/m/Y à H:i') }}</p>
                                @if($withdrawal->processed_by)
                                    <p class="text-xs text-gray-400">Par {{ $withdrawal->processedBy->first_name ?? 'Admin' }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($withdrawal->status == 'rejected')
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-times text-red-600 text-sm"></i>
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-gray-900">Demande rejetée</p>
                                <p class="text-sm text-gray-500">{{ $withdrawal->processed_at?->format('d/m/Y à H:i') ?? 'Date inconnue' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Actions -->
        @if($withdrawal->status == 'pending')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>
                <div class="space-y-3">
                    <form action="{{ route('admin.withdrawals.process', $withdrawal) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Référence</label>
                            <input type="text" name="transaction_reference" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-check mr-2"></i>Marquer comme payé
                        </button>
                    </form>

                    <form action="{{ route('admin.withdrawals.reject', $withdrawal) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Raison du rejet</label>
                            <textarea name="rejection_reason" rows="2" required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"></textarea>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-times mr-2"></i>Rejeter
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- User Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Utilisateur</h3>
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold">
                    {{ strtoupper(substr($withdrawal->user->first_name ?? 'U', 0, 1)) }}
                </div>
                <div class="ml-3">
                    <p class="font-medium text-gray-900">{{ $withdrawal->user->first_name ?? '' }} {{ $withdrawal->user->last_name ?? '' }}</p>
                    <p class="text-sm text-gray-500">{{ '@' . ($withdrawal->user->username ?? 'unknown') }}</p>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Solde actuel</span>
                    <span class="font-medium">{{ number_format($withdrawal->user->wallet->balance ?? 0) }} FCFA</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total retraits</span>
                    <span class="font-medium">{{ $withdrawal->user->withdrawals()->completed()->count() }}</span>
                </div>
            </div>

            <a href="{{ route('admin.users.show', $withdrawal->user) }}"
               class="mt-4 block text-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                Voir le profil complet
            </a>
        </div>
    </div>
</div>
@endsection
