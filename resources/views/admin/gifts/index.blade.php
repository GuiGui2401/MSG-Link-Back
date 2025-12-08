@extends('admin.layouts.app')

@section('title', 'Cadeaux')
@section('header', 'Transactions de cadeaux')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-gift text-pink-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total cadeaux</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total'] ?? 0) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-coins text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Montant total</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_amount'] ?? 0) }} FCFA</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-percentage text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Commission plateforme</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['platform_fees'] ?? 0) }} FCFA</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Ce mois</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['this_month'] ?? 0) }} FCFA</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher par utilisateur..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
        </div>
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="">Tous les statuts</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Complétés</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Échoués</option>
        </select>
        <select name="tier" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="">Tous les niveaux</option>
            <option value="bronze" {{ request('tier') == 'bronze' ? 'selected' : '' }}>Bronze</option>
            <option value="silver" {{ request('tier') == 'silver' ? 'selected' : '' }}>Argent</option>
            <option value="gold" {{ request('tier') == 'gold' ? 'selected' : '' }}>Or</option>
            <option value="diamond" {{ request('tier') == 'diamond' ? 'selected' : '' }}>Diamant</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            <i class="fas fa-search mr-2"></i>Filtrer
        </button>
        @if(request()->hasAny(['search', 'status', 'tier']))
            <a href="{{ route('admin.gifts.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                <i class="fas fa-times mr-1"></i>Réinitialiser
            </a>
        @endif
    </form>
</div>

<!-- Gifts Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expéditeur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destinataire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cadeau</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($gifts as $gift)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #{{ $gift->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($gift->sender)
                                <a href="{{ route('admin.users.show', $gift->sender) }}" class="flex items-center hover:text-primary-600">
                                    <div class="w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-pink-600">{{ $gift->sender->initial }}</span>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $gift->sender->username }}</span>
                                </a>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($gift->recipient)
                                <a href="{{ route('admin.users.show', $gift->recipient) }}" class="flex items-center hover:text-primary-600">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-purple-600">{{ $gift->recipient->initial }}</span>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $gift->recipient->username }}</span>
                                </a>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $tierColors = [
                                    'bronze' => 'bg-orange-100 text-orange-700',
                                    'silver' => 'bg-gray-200 text-gray-700',
                                    'gold' => 'bg-yellow-100 text-yellow-700',
                                    'diamond' => 'bg-blue-100 text-blue-700',
                                ];
                                $tierIcons = [
                                    'bronze' => 'fa-medal',
                                    'silver' => 'fa-medal',
                                    'gold' => 'fa-crown',
                                    'diamond' => 'fa-gem',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $tierColors[$gift->gift?->tier] ?? 'bg-gray-100 text-gray-700' }}">
                                <i class="fas {{ $tierIcons[$gift->gift?->tier] ?? 'fa-gift' }} mr-1"></i>
                                {{ ucfirst($gift->gift?->tier ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-900">{{ number_format($gift->amount) }} FCFA</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ number_format($gift->platform_fee ?? 0) }} FCFA</span>
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
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$gift->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $statusLabels[$gift->status] ?? $gift->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $gift->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-gift text-4xl mb-4"></i>
                            <p>Aucune transaction de cadeau trouvée</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($gifts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $gifts->links() }}
        </div>
    @endif
</div>
@endsection
