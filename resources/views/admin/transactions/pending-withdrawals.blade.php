@extends('admin.layouts.app')

@section('title', 'Demandes de retrait en attente')
@section('header', 'Demandes de retrait en attente')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Liste des demandes de retrait en attente ({{ $transactions->total() }})</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Méthode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $transaction->user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $transaction->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-red-600 font-semibold">-{{ number_format(abs($transaction->amount)) }} FCFA</td>
                        <td class="px-6 py-4">
                            @php
                                $meta = $transaction->meta ?? [];
                                $method = $meta['operator'] ?? $meta['method'] ?? 'N/A';
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ ucfirst(str_replace('_', ' ', $method)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @php
                                $phoneNumber = $meta['phone_number'] ?? 'N/A';
                            @endphp
                            {{ $phoneNumber }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="approveTransaction({{ $transaction->id }})" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors" title="Approuver">
                                    <i class="fas fa-check mr-1"></i>Approuver
                                </button>
                                <button type="button" onclick="rejectTransaction({{ $transaction->id }})" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition-colors" title="Rejeter">
                                    <i class="fas fa-times mr-1"></i>Rejeter
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">Aucune demande de retrait en attente</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- Overlay de chargement -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md mx-4">
        <div class="flex flex-col items-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-primary-600 mb-4"></div>
            <div class="text-lg font-semibold text-gray-800 mb-2">Traitement en cours...</div>
            <div class="text-sm text-gray-600 text-center">
                Envoi du transfert vers CinetPay.<br>
                Veuillez patienter 1-3 minutes.<br>
                <strong>Ne fermez pas cette page.</strong>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
    document.getElementById('loading-overlay').classList.add('flex');
}

function hideLoading() {
    document.getElementById('loading-overlay').classList.add('hidden');
    document.getElementById('loading-overlay').classList.remove('flex');
}

function approveTransaction(id) {
    if (confirm('Approuver cette demande de retrait ?\n\nLe transfert CinetPay sera exécuté immédiatement.\nCela peut prendre 1-3 minutes.')) {
        showLoading();

        fetch(`/admin/transactions/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
        })
        .then(response => response.text())
        .then(html => {
            location.reload();
        })
        .catch(error => {
            hideLoading();
            alert('Erreur lors de l\'approbation: ' + error.message);
        });
    }
}

function rejectTransaction(id) {
    if (confirm('Rejeter cette demande de retrait ?')) {
        showLoading();

        fetch(`/admin/transactions/${id}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
        })
        .then(() => location.reload())
        .catch(error => {
            hideLoading();
            alert('Erreur lors du rejet: ' + error.message);
        });
    }
}
</script>
@endpush
@endsection
