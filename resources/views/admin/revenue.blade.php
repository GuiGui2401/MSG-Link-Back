@extends('admin.layouts.app')

@section('title', 'Revenus')
@section('header', 'Revenus')

@section('content')
<!-- Period Selector -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form action="{{ route('admin.revenue') }}" method="GET" class="flex flex-wrap items-center gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Date début</label>
            <input type="date" name="from" value="{{ request('from', now()->startOfMonth()->toDateString()) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Date fin</label>
            <input type="date" name="to" value="{{ request('to', now()->toDateString()) }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div class="pt-5">
            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm">Total Revenus</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($summary['total'] ?? 0) }}</p>
                <p class="text-green-100 text-sm">FCFA</p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm">Commissions plateforme</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($summary['platform_fees'] ?? 0) }}</p>
                <p class="text-purple-100 text-sm">FCFA</p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-percentage text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm">Abonnements</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($summary['subscriptions'] ?? 0) }}</p>
                <p class="text-blue-100 text-sm">FCFA</p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-star text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-r from-pink-500 to-pink-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-pink-100 text-sm">Transactions</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($summary['transactions_count'] ?? 0) }}</p>
                <p class="text-pink-100 text-sm">opérations</p>
            </div>
            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-exchange-alt text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts & Details -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Revenue by Type -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-chart-pie text-primary-500 mr-2"></i>
            Revenus par type
        </h3>
        <div class="relative h-64">
            <canvas id="revenueByTypeChart"></canvas>
        </div>

        <div class="mt-4 space-y-2">
            @foreach($byType ?? [] as $type)
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <span class="font-medium text-gray-700 capitalize">{{ $type->type }}</span>
                    <span class="font-bold text-gray-900">{{ number_format($type->total) }} FCFA</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Revenue by Provider -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-credit-card text-blue-500 mr-2"></i>
            Revenus par provider
        </h3>
        <div class="relative h-64">
            <canvas id="revenueByProviderChart"></canvas>
        </div>

        <div class="mt-4 space-y-2">
            @foreach($byProvider ?? [] as $provider)
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <div class="flex items-center">
                        @if($provider->provider == 'mtn_momo')
                            <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                        @elseif($provider->provider == 'orange_money')
                            <span class="w-3 h-3 bg-orange-500 rounded-full mr-2"></span>
                        @else
                            <span class="w-3 h-3 bg-gray-500 rounded-full mr-2"></span>
                        @endif
                        <span class="font-medium text-gray-700">{{ strtoupper(str_replace('_', ' ', $provider->provider)) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="font-bold text-gray-900">{{ number_format($provider->total) }} FCFA</span>
                        <span class="text-xs text-gray-500 ml-2">({{ $provider->count }} trans.)</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Dernières transactions</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Provider</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($recentPayments ?? [] as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">#{{ $payment->id }}</td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $payment->user->first_name ?? '' }} {{ $payment->user->last_name ?? '' }}</p>
                            <p class="text-xs text-gray-500">{{ '@' . ($payment->user->username ?? 'unknown') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium capitalize">{{ $payment->type }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-green-600">{{ number_format($payment->amount) }} FCFA</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ strtoupper(str_replace('_', ' ', $payment->provider)) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->completed_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-receipt text-4xl mb-3"></i>
                            <p>Aucune transaction trouvée</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue by Type Chart
    new Chart(document.getElementById('revenueByTypeChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(collect($byType ?? [])->pluck('type')->map(fn($t) => ucfirst($t))) !!},
            datasets: [{
                data: {!! json_encode(collect($byType ?? [])->pluck('total')) !!},
                backgroundColor: [
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(234, 179, 8, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Revenue by Provider Chart
    new Chart(document.getElementById('revenueByProviderChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(collect($byProvider ?? [])->pluck('provider')->map(fn($p) => strtoupper(str_replace('_', ' ', $p)))) !!},
            datasets: [{
                label: 'Montant (FCFA)',
                data: {!! json_encode(collect($byProvider ?? [])->pluck('total')) !!},
                backgroundColor: [
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(59, 130, 246, 0.8)'
                ],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endpush
