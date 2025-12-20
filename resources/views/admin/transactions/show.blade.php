@extends('admin.layouts.app')

@section('title', 'Détails de la transaction')
@section('header', 'Transaction #' . $transaction->id)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Détails de la transaction</h2>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Colonne gauche -->
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">ID</label>
                        <p class="text-gray-900 font-semibold">#{{ $transaction->id }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Utilisateur</label>
                        <p class="text-gray-900 font-semibold">{{ $transaction->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $transaction->user->email }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Type</label>
                        <div class="mt-1">
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                @if($transaction->type === 'deposit') bg-blue-100 text-blue-800
                                @elseif($transaction->type === 'withdrawal') bg-yellow-100 text-yellow-800
                                @elseif($transaction->type === 'bonus') bg-green-100 text-green-800
                                @elseif($transaction->type === 'affiliate_commission') bg-purple-100 text-purple-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($transaction->type) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Montant</label>
                        <p class="text-2xl font-bold {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }} FCFA
                        </p>
                    </div>
                </div>

                <!-- Colonne droite -->
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Statut</label>
                        <div class="mt-1">
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($transaction->status === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Date de création</label>
                        <p class="text-gray-900">{{ $transaction->created_at->format('d/m/Y à H:i:s') }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Description</label>
                        <p class="text-gray-900">{{ $transaction->description ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            @if($transaction->meta)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <label class="text-sm font-medium text-gray-500 mb-2 block">Métadonnées</label>
                    <pre class="bg-gray-50 p-4 rounded-lg text-sm text-gray-800 overflow-x-auto">{{ json_encode($transaction->meta, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            @if($transaction->type === 'withdrawal' && $transaction->status === 'pending')
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center space-x-4">
                        <button type="button" onclick="approveTransaction({{ $transaction->id }})" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-check mr-2"></i>
                            Approuver
                        </button>
                        <button type="button" onclick="rejectTransaction({{ $transaction->id }})" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-times mr-2"></i>
                            Rejeter
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center text-gray-700 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à la liste
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function approveTransaction(id) {
    if (confirm('Approuver cette demande de retrait ?')) {
        fetch(`/admin/transactions/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        }).then(() => location.reload());
    }
}

function rejectTransaction(id) {
    if (confirm('Rejeter cette demande de retrait ?')) {
        fetch(`/admin/transactions/${id}/reject`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        }).then(() => location.reload());
    }
}
</script>
@endpush
@endsection
