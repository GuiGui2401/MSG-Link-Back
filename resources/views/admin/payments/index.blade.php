@extends('admin.layouts.app')

@section('title', 'Paiements')
@section('header', 'Gestion des paiements')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total réussis</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['completed_amount'] ?? 0) }} FCFA</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-receipt text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Ce mois</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['this_month'] ?? 0) }} FCFA</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">En attente</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_count'] ?? 0 }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total transactions</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_count'] ?? 0) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher par référence ou email..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
        </div>
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="">Tous les statuts</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Complétés</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Échoués</option>
        </select>
        <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="">Tous les types</option>
            <option value="subscription" {{ request('type') == 'subscription' ? 'selected' : '' }}>Abonnements</option>
            <option value="gift" {{ request('type') == 'gift' ? 'selected' : '' }}>Cadeaux</option>
            <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>Retraits</option>
        </select>
        <select name="provider" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="">Tous les providers</option>
            <option value="cinetpay" {{ request('provider') == 'cinetpay' ? 'selected' : '' }}>CinetPay</option>
            <option value="ligosapp" {{ request('provider') == 'ligosapp' ? 'selected' : '' }}>LigosApp</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            <i class="fas fa-search mr-2"></i>Filtrer
        </button>
        @if(request()->hasAny(['search', 'status', 'type', 'provider']))
            <a href="{{ route('admin.payments.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                <i class="fas fa-times mr-1"></i>Réinitialiser
            </a>
        @endif
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Référence</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono text-gray-900">{{ $payment->reference }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payment->user)
                                <a href="{{ route('admin.users.show', $payment->user) }}" class="flex items-center hover:text-primary-600">
                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-gray-600">{{ $payment->user->initial }}</span>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $payment->user->username }}</span>
                                </a>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $typeColors[$payment->type] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $typeLabels[$payment->type] ?? $payment->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($payment->amount) }} FCFA</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600 capitalize">{{ $payment->provider }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$payment->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $statusLabels[$payment->status] ?? $payment->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $payment->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-primary-600 hover:text-primary-700">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-4"></i>
                            <p>Aucun paiement trouvé</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
