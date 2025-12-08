@extends('admin.layouts.app')

@section('title', 'Analytics')
@section('header', 'Analytics')

@section('content')
<!-- Period Selector -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form action="{{ route('admin.analytics') }}" method="GET" class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700">Période:</label>
        <select name="period" onchange="this.form.submit()"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="7" {{ request('period', 30) == 7 ? 'selected' : '' }}>7 derniers jours</option>
            <option value="30" {{ request('period', 30) == 30 ? 'selected' : '' }}>30 derniers jours</option>
            <option value="90" {{ request('period', 30) == 90 ? 'selected' : '' }}>90 derniers jours</option>
            <option value="365" {{ request('period', 30) == 365 ? 'selected' : '' }}>12 derniers mois</option>
        </select>
    </form>
</div>

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- User Registrations -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-user-plus text-blue-500 mr-2"></i>
            Inscriptions
        </h3>
        <div class="relative h-64">
            <canvas id="registrationsChart"></canvas>
        </div>
    </div>

    <!-- Messages -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-envelope text-purple-500 mr-2"></i>
            Messages envoyés
        </h3>
        <div class="relative h-64">
            <canvas id="messagesChart"></canvas>
        </div>
    </div>

    <!-- Revenue -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>
            Revenus (FCFA)
        </h3>
        <div class="relative h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Gifts by Tier -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-gift text-pink-500 mr-2"></i>
            Répartition des cadeaux
        </h3>
        <div class="relative h-64">
            <canvas id="giftsChart"></canvas>
        </div>
    </div>
</div>

<!-- Rankings -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Top Users by Messages -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                Top utilisateurs (Messages reçus)
            </h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($rankings['top_by_messages'] ?? [] as $index => $user)
                <div class="px-6 py-4 flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                        @if($index == 0) bg-yellow-100 text-yellow-700
                        @elseif($index == 1) bg-gray-200 text-gray-700
                        @elseif($index == 2) bg-orange-100 text-orange-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ $index + 1 }}
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="font-medium text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                        <p class="text-sm text-gray-500">{{ '@' . $user->username }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-primary-600">{{ number_format($user->received_messages_count) }}</p>
                        <p class="text-xs text-gray-500">messages</p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>Aucune donnée disponible</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Top Users by Gifts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-gift text-pink-500 mr-2"></i>
                Top utilisateurs (Cadeaux reçus)
            </h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($rankings['top_by_gifts'] ?? [] as $index => $user)
                <div class="px-6 py-4 flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                        @if($index == 0) bg-yellow-100 text-yellow-700
                        @elseif($index == 1) bg-gray-200 text-gray-700
                        @elseif($index == 2) bg-orange-100 text-orange-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ $index + 1 }}
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="font-medium text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                        <p class="text-sm text-gray-500">{{ '@' . $user->username }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600">{{ number_format($user->gifts_value ?? 0) }}</p>
                        <p class="text-xs text-gray-500">FCFA</p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <p>Aucune donnée disponible</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                grid: { display: false }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' }
            }
        }
    };

    // Registrations Chart
    new Chart(document.getElementById('registrationsChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($charts['user_registrations']->pluck('date') ?? []) !!},
            datasets: [{
                label: 'Inscriptions',
                data: {!! json_encode($charts['user_registrations']->pluck('count') ?? []) !!},
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: chartOptions
    });

    // Messages Chart
    new Chart(document.getElementById('messagesChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($charts['messages_per_day']->pluck('date') ?? []) !!},
            datasets: [{
                label: 'Messages',
                data: {!! json_encode($charts['messages_per_day']->pluck('count') ?? []) !!},
                borderColor: '#a855f7',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: chartOptions
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($charts['revenue_per_day']->pluck('date') ?? []) !!},
            datasets: [{
                label: 'Revenus',
                data: {!! json_encode($charts['revenue_per_day']->pluck('total') ?? []) !!},
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderRadius: 4
            }]
        },
        options: chartOptions
    });

    // Gifts Distribution Chart
    new Chart(document.getElementById('giftsChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($distributions['gifts_by_tier']->pluck('tier') ?? []) !!},
            datasets: [{
                data: {!! json_encode($distributions['gifts_by_tier']->pluck('count') ?? []) !!},
                backgroundColor: [
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(234, 179, 8, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>
@endpush
