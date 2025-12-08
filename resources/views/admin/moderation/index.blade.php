@extends('admin.layouts.app')

@section('title', 'Modération')
@section('header', 'Centre de modération')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-flag text-red-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['reports_pending'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Signalements en attente</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-heart text-yellow-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['confessions_pending'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Confessions à modérer</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['resolved_today'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Résolus aujourd'hui</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-slash text-purple-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['bans_today'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Bannissements aujourd'hui</p>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div x-data="{ activeTab: 'reports' }" class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="flex space-x-8">
            <button @click="activeTab = 'reports'"
                    :class="activeTab === 'reports' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-flag mr-2"></i>Signalements
                @if(($stats['reports_pending'] ?? 0) > 0)
                    <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 rounded-full text-xs">{{ $stats['reports_pending'] }}</span>
                @endif
            </button>
            <button @click="activeTab = 'confessions'"
                    :class="activeTab === 'confessions' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-heart mr-2"></i>Confessions à modérer
                @if(($stats['confessions_pending'] ?? 0) > 0)
                    <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-600 rounded-full text-xs">{{ $stats['confessions_pending'] }}</span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Reports Tab -->
    <div x-show="activeTab === 'reports'" class="mt-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Signalé par</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Raison</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($reports ?? [] as $report)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                            {{ strtoupper(substr($report->reporter->first_name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $report->reporter->first_name ?? 'Utilisateur' }}</p>
                                            <p class="text-xs text-gray-500">{{ '@' . ($report->reporter->username ?? 'unknown') }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                        {{ ucfirst($report->reportable_type ?? 'Contenu') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ $report->reason }}</p>
                                    @if($report->description)
                                        <p class="text-xs text-gray-500 truncate max-w-xs">{{ $report->description }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $report->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($report->status == 'pending')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">En attente</span>
                                    @elseif($report->status == 'resolved')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Résolu</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">Rejeté</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('admin.moderation.report', $report) }}"
                                           class="p-2 text-gray-400 hover:text-primary-600 transition-colors" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($report->status == 'pending')
                                            <form action="{{ route('admin.moderation.resolve', $report) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-green-600 transition-colors" title="Résoudre">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.moderation.dismiss', $report) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Rejeter">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-check-circle text-4xl mb-3 text-green-400"></i>
                                    <p>Aucun signalement en attente</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Confessions Tab -->
    <div x-show="activeTab === 'confessions'" x-cloak class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($pendingConfessions ?? [] as $confession)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center text-pink-600">
                                    <i class="fas fa-heart text-sm"></i>
                                </div>
                                <div class="ml-2">
                                    <p class="text-sm font-medium text-gray-900">{{ $confession->is_anonymous ? 'Anonyme' : $confession->author->first_name ?? 'Utilisateur' }}</p>
                                    <p class="text-xs text-gray-500">{{ $confession->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">En attente</span>
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
                    </div>

                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-2">
                        <form action="{{ route('admin.confessions.reject', $confession) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-sm hover:bg-red-200 transition-colors">
                                <i class="fas fa-times mr-1"></i>Rejeter
                            </button>
                        </form>
                        <form action="{{ route('admin.confessions.approve', $confession) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition-colors">
                                <i class="fas fa-check mr-1"></i>Approuver
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500">
                    <i class="fas fa-heart text-4xl mb-3 text-pink-300"></i>
                    <p>Aucune confession en attente de modération</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
