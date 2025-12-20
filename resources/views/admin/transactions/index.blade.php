@extends('admin.layouts.app')

@section('title', 'Gestion des transactions')
@section('header', 'Transactions')

@section('content')
<!-- Filtres -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <div class="md:col-span-2">
            <input type="text" name="search" placeholder="Rechercher utilisateur..." value="{{ request('search') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>
        <div>
            <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">Tous types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">Tous statuts</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>
        <div>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>
        <div>
            <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
        </div>
    </form>
</div>

<!-- Table des transactions -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Liste des transactions ({{ $transactions->total() }})</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
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
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @if($transaction->type === 'deposit') bg-blue-100 text-blue-800
                                @elseif($transaction->type === 'withdrawal') bg-yellow-100 text-yellow-800
                                @elseif($transaction->type === 'bonus') bg-green-100 text-green-800
                                @elseif($transaction->type === 'affiliate_commission') bg-purple-100 text-purple-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }} FCFA
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ \Illuminate\Support\Str::limit($transaction->description, 50) }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($transaction->status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.transactions.show', $transaction) }}" class="text-blue-600 hover:text-blue-800" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($transaction->type === 'withdrawal' && $transaction->status === 'pending')
                                    <button type="button" onclick="approveTransaction({{ $transaction->id }})" class="text-green-600 hover:text-green-800" title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" onclick="rejectTransaction({{ $transaction->id }})" class="text-red-600 hover:text-red-800" title="Rejeter">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Aucune transaction trouvée</td>
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
