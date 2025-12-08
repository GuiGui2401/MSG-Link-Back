@extends('admin.layouts.app')

@section('title', 'Retraits')
@section('header', 'Gestion des retraits')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_count'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">En attente</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-coins text-orange-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending_amount'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">FCFA en attente</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ $stats['completed_today'] ?? 0 }}</p>
                <p class="text-sm text-gray-500">Traités aujourd'hui</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-blue-600"></i>
            </div>
            <div class="ml-3">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['completed_this_month'] ?? 0) }}</p>
                <p class="text-sm text-gray-500">FCFA ce mois</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form action="{{ route('admin.withdrawals.index') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="w-40">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Tous les statuts</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>En cours</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Complété</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
            </select>
        </div>
        <div class="w-40">
            <select name="method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                <option value="">Toutes les méthodes</option>
                <option value="mtn_momo" {{ request('method') == 'mtn_momo' ? 'selected' : '' }}>MTN MoMo</option>
                <option value="orange_money" {{ request('method') == 'orange_money' ? 'selected' : '' }}>Orange Money</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-filter mr-2"></i>Filtrer
        </button>
        @if(request()->hasAny(['status', 'method']))
            <a href="{{ route('admin.withdrawals.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-times mr-2"></i>Réinitialiser
            </a>
        @endif
        <a href="{{ route('admin.withdrawals.export') }}" class="ml-auto px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-download mr-2"></i>Exporter
        </a>
    </form>
</div>

<!-- Withdrawals Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Méthode</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Numéro</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($withdrawals as $withdrawal)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">#{{ $withdrawal->id }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                    {{ strtoupper(substr($withdrawal->user->first_name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ $withdrawal->user->first_name ?? '' }} {{ $withdrawal->user->last_name ?? '' }}</p>
                                    <p class="text-xs text-gray-500">{{ '@' . ($withdrawal->user->username ?? 'unknown') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-gray-900">{{ number_format($withdrawal->amount) }} FCFA</p>
                            <p class="text-xs text-gray-500">Net: {{ number_format($withdrawal->net_amount) }} FCFA</p>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $methodIcons = [
                                    'mtn_momo' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'icon' => 'fas fa-mobile-alt'],
                                    'orange_money' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'icon' => 'fas fa-mobile-alt'],
                                ];
                                $method = $methodIcons[$withdrawal->method] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'icon' => 'fas fa-wallet'];
                            @endphp
                            <span class="inline-flex items-center px-2 py-1 {{ $method['bg'] }} {{ $method['text'] }} rounded text-xs font-medium">
                                <i class="{{ $method['icon'] }} mr-1"></i>
                                {{ strtoupper(str_replace('_', ' ', $withdrawal->method)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-mono">{{ $withdrawal->phone_number }}</td>
                        <td class="px-6 py-4">
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
                            <span class="px-2 py-1 {{ $statusColors[$withdrawal->status] ?? 'bg-gray-100 text-gray-700' }} rounded-full text-xs font-medium">
                                {{ $statusLabels[$withdrawal->status] ?? $withdrawal->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900">{{ $withdrawal->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $withdrawal->created_at->format('H:i') }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @if($withdrawal->status == 'pending')
                                    <button type="button"
                                            onclick="openProcessModal({{ $withdrawal->id }}, '{{ $withdrawal->user->first_name }}', {{ $withdrawal->net_amount }}, '{{ $withdrawal->phone_number }}')"
                                            class="p-2 text-gray-400 hover:text-green-600 transition-colors" title="Traiter">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <button type="button"
                                            onclick="openRejectModal({{ $withdrawal->id }})"
                                            class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Rejeter">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                @endif
                                <a href="{{ route('admin.withdrawals.show', $withdrawal) }}"
                                   class="p-2 text-gray-400 hover:text-primary-600 transition-colors" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-wallet text-4xl mb-3"></i>
                            <p>Aucune demande de retrait</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($withdrawals->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $withdrawals->links() }}
        </div>
    @endif
</div>

<!-- Process Modal -->
<div id="processModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Traiter le retrait</h3>
        </div>
        <form id="processForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="bg-green-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-green-800">
                        <strong>Utilisateur:</strong> <span id="processUserName"></span><br>
                        <strong>Montant net:</strong> <span id="processAmount"></span> FCFA<br>
                        <strong>Numéro:</strong> <span id="processPhone"></span>
                    </p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Référence de transaction</label>
                    <input type="text" name="transaction_reference" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                           placeholder="Ex: TXN123456789">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optionnel)</label>
                    <textarea name="notes" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                              placeholder="Notes additionnelles..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button type="button" onclick="closeProcessModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>Confirmer le paiement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Rejeter le retrait</h3>
        </div>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="bg-red-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-red-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Le montant sera recrédité au solde de l'utilisateur.
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Raison du rejet *</label>
                    <textarea name="rejection_reason" rows="3" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                              placeholder="Expliquez la raison du rejet..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Annuler
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-times mr-2"></i>Rejeter
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openProcessModal(id, userName, amount, phone) {
    document.getElementById('processUserName').textContent = userName;
    document.getElementById('processAmount').textContent = amount.toLocaleString();
    document.getElementById('processPhone').textContent = phone;
    document.getElementById('processForm').action = '/admin/withdrawals/' + id + '/process';
    document.getElementById('processModal').classList.remove('hidden');
    document.getElementById('processModal').classList.add('flex');
}

function closeProcessModal() {
    document.getElementById('processModal').classList.add('hidden');
    document.getElementById('processModal').classList.remove('flex');
}

function openRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/withdrawals/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

// Close modals on backdrop click
document.getElementById('processModal').addEventListener('click', function(e) {
    if (e.target === this) closeProcessModal();
});
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endpush
