@extends('admin.layouts.app')

@section('title', 'Paiement #' . $payment->reference)
@section('header', 'Détails du paiement')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.payments.index') }}" class="text-primary-600 hover:text-primary-700">
        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Payment Details -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="font-mono text-gray-700">{{ $payment->reference }}</span>
                    @php
                        $statusColors = [
                            'completed' => 'bg-green-100 text-green-700',
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'failed' => 'bg-red-100 text-red-700',
                            'refunded' => 'bg-gray-100 text-gray-700',
                        ];
                        $statusLabels = [
                            'completed' => 'Complété',
                            'pending' => 'En attente',
                            'failed' => 'Échoué',
                            'refunded' => 'Remboursé',
                        ];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$payment->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $statusLabels[$payment->status] ?? $payment->status }}
                    </span>
                </div>
                <span class="text-sm text-gray-500">{{ $payment->created_at->format('d/m/Y à H:i') }}</span>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Montant</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($payment->amount) }} FCFA</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Type</p>
                        @php
                            $typeColors = [
                                'subscription' => 'bg-purple-100 text-purple-700',
                                'gift' => 'bg-pink-100 text-pink-700',
                                'withdrawal' => 'bg-blue-100 text-blue-700',
                            ];
                            $typeLabels = [
                                'subscription' => 'Abonnement',
                                'gift' => 'Cadeau',
                                'withdrawal' => 'Retrait',
                            ];
                        @endphp
                        <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{ $typeColors[$payment->type] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $typeLabels[$payment->type] ?? $payment->type }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Détails de la transaction</h3>
            <div class="space-y-4">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Référence</span>
                    <span class="font-mono text-gray-900">{{ $payment->reference }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Provider</span>
                    <span class="text-gray-900 capitalize">{{ $payment->provider }}</span>
                </div>
                @if($payment->provider_reference)
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Référence provider</span>
                    <span class="font-mono text-gray-900">{{ $payment->provider_reference }}</span>
                </div>
                @endif
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Date de création</span>
                    <span class="text-gray-900">{{ $payment->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @if($payment->completed_at)
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Date de complétion</span>
                    <span class="text-gray-900">{{ $payment->completed_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif
                @if($payment->metadata)
                <div class="py-2">
                    <span class="text-gray-500 block mb-2">Métadonnées</span>
                    <pre class="bg-gray-50 p-3 rounded-lg text-sm overflow-x-auto">{{ json_encode($payment->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- User Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Utilisateur</h3>
            @if($payment->user)
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                        @if($payment->user->avatar)
                            <img src="{{ $payment->user->avatar_url }}" alt="" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <span class="text-xl font-bold text-primary-600">{{ $payment->user->initial }}</span>
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('admin.users.show', $payment->user) }}" class="font-medium text-gray-900 hover:text-primary-600">
                            {{ $payment->user->full_name }}
                        </a>
                        <p class="text-sm text-gray-500">{{ '@' . $payment->user->username }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="text-gray-900">{{ $payment->user->email }}</p>
                </div>
            @else
                <p class="text-gray-500">Utilisateur non disponible</p>
            @endif
        </div>

        <!-- Status Timeline -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Historique</h3>
            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-plus text-gray-500 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Création</p>
                        <p class="text-xs text-gray-500">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @if($payment->status === 'completed' && $payment->completed_at)
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Paiement complété</p>
                        <p class="text-xs text-gray-500">{{ $payment->completed_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @elseif($payment->status === 'failed')
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-times text-red-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Paiement échoué</p>
                        <p class="text-xs text-gray-500">{{ $payment->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @elseif($payment->status === 'pending')
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-clock text-yellow-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">En attente</p>
                        <p class="text-xs text-gray-500">En cours de traitement</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
