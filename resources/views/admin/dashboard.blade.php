@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Users Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Utilisateurs</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['users']['total'] ?? 0) }}</p>
                <p class="text-sm text-green-600 mt-2">
                    <i class="fas fa-arrow-up mr-1"></i>
                    +{{ $stats['users']['today'] ?? 0 }} aujourd'hui
                </p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-2xl text-blue-600"></i>
            </div>
        </div>
    </div>

    <!-- Messages Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Messages</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['messages']['total'] ?? 0) }}</p>
                <p class="text-sm text-green-600 mt-2">
                    <i class="fas fa-arrow-up mr-1"></i>
                    +{{ $stats['messages']['today'] ?? 0 }} aujourd'hui
                </p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-envelope text-2xl text-purple-600"></i>
            </div>
        </div>
    </div>

    <!-- Revenue Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Revenus (Mois)</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['revenue']['this_month'] ?? 0) }} <span class="text-lg">FCFA</span></p>
                <p class="text-sm text-green-600 mt-2">
                    <i class="fas fa-coins mr-1"></i>
                    {{ number_format($stats['revenue']['platform_fees'] ?? 0) }} FCFA commissions
                </p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
            </div>
        </div>
    </div>

    <!-- Pending Actions Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Actions en attente</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ ($stats['withdrawals']['pending'] ?? 0) + ($stats['reports']['pending'] ?? 0) + ($stats['confessions']['pending'] ?? 0) }}</p>
                <div class="flex items-center space-x-3 mt-2 text-sm">
                    <span class="text-yellow-600">{{ $stats['withdrawals']['pending'] ?? 0 }} retraits</span>
                    <span class="text-red-600">{{ $stats['reports']['pending'] ?? 0 }} signalements</span>
                </div>
            </div>
            <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-2xl text-yellow-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Users Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Inscriptions (30 jours)</h3>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 bg-primary-500 rounded-full"></span>
                <span class="text-sm text-gray-500">Utilisateurs</span>
            </div>
        </div>
        <div class="relative h-64">
            <canvas id="usersChart"></canvas>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Revenus (30 jours)</h3>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                <span class="text-sm text-gray-500">FCFA</span>
            </div>
        </div>
        <div class="relative h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Confessions Stats -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-heart text-pink-500 mr-2"></i>
            Confessions
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Total</span>
                <span class="font-semibold">{{ number_format($stats['confessions']['total'] ?? 0) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">En attente</span>
                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">{{ $stats['confessions']['pending'] ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Approuvées</span>
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">{{ $stats['confessions']['approved'] ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Rejetées</span>
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">{{ $stats['confessions']['rejected'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <!-- Chat Stats -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-comments text-blue-500 mr-2"></i>
            Conversations
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Total</span>
                <span class="font-semibold">{{ number_format($stats['chat']['conversations'] ?? 0) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Actives aujourd'hui</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">{{ $stats['chat']['active_today'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <!-- Withdrawals Stats -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-wallet text-green-500 mr-2"></i>
            Retraits
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">En attente</span>
                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">{{ $stats['withdrawals']['pending'] ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Montant en attente</span>
                <span class="font-semibold">{{ number_format($stats['withdrawals']['pending_amount'] ?? 0) }} FCFA</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Traités ce mois</span>
                <span class="font-semibold text-green-600">{{ number_format($stats['withdrawals']['completed_this_month'] ?? 0) }} FCFA</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Users -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Nouveaux utilisateurs</h3>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-primary-600 hover:text-primary-700">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($recentUsers ?? [] as $user)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold">
                            {{ strtoupper(substr($user->first_name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                            <p class="text-sm text-gray-500">{{ '@' . $user->username }}</p>
                        </div>
                    </div>
                    <span class="text-xs text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-users text-3xl mb-2"></i>
                    <p>Aucun utilisateur récent</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Derniers paiements</h3>
            <a href="{{ route('admin.revenue') }}" class="text-sm text-primary-600 hover:text-primary-700">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($recentPayments ?? [] as $payment)
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">{{ number_format($payment->amount) }} FCFA</p>
                            <p class="text-sm text-gray-500">{{ $payment->user->username ?? 'Utilisateur' }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">{{ ucfirst($payment->type) }}</span>
                        <p class="text-xs text-gray-400 mt-1">{{ $payment->completed_at?->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-receipt text-3xl mb-2"></i>
                    <p>Aucun paiement récent</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Users Chart
    const usersCtx = document.getElementById('usersChart').getContext('2d');
    new Chart(usersCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['users']['labels'] ?? []) !!},
            datasets: [{
                label: 'Inscriptions',
                data: {!! json_encode($chartData['users']['data'] ?? []) !!},
                borderColor: '#d946ef',
                backgroundColor: 'rgba(217, 70, 239, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#d946ef',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 7 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartData['revenue']['labels'] ?? []) !!},
            datasets: [{
                label: 'Revenus (FCFA)',
                data: {!! json_encode($chartData['revenue']['data'] ?? []) !!},
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderColor: '#22c55e',
                borderWidth: 1,
                borderRadius: 4,
                barThickness: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 7 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
