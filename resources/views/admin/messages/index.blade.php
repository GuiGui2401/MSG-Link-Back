@extends('admin.layouts.app')

@section('title', 'Messages anonymes')
@section('header', 'Messages anonymes')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-envelope text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Total messages</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total'] ?? 0) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Aujourd'hui</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['today'] ?? 0) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-eye text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Lus</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['read'] ?? 0) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-flag text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Signalés</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['reported'] ?? 0) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher dans les messages..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
        </div>
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="">Tous les statuts</option>
            <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Lus</option>
            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Non lus</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            <i class="fas fa-search mr-2"></i>Filtrer
        </button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.messages.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                <i class="fas fa-times mr-1"></i>Réinitialiser
            </a>
        @endif
    </form>
</div>

<!-- Messages Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expéditeur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destinataire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contenu</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($messages as $message)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #{{ $message->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($message->sender)
                                <a href="{{ route('admin.users.show', $message->sender) }}" class="flex items-center hover:text-primary-600">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-purple-600">{{ $message->sender->initial }}</span>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $message->sender->username }}</span>
                                </a>
                            @else
                                <span class="text-sm text-gray-500 italic">Anonyme</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($message->recipient)
                                <a href="{{ route('admin.users.show', $message->recipient) }}" class="flex items-center hover:text-primary-600">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-blue-600">{{ $message->recipient->initial }}</span>
                                    </div>
                                    <span class="text-sm text-gray-900">{{ $message->recipient->username }}</span>
                                </a>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-900 max-w-xs truncate">{{ $message->content }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($message->read_at)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Lu</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">Non lu</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $message->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <button onclick="showMessage({{ $message->id }}, '{{ addslashes($message->content) }}')"
                                    class="text-primary-600 hover:text-primary-700 mr-3">
                                <i class="fas fa-eye"></i>
                            </button>
                            <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-envelope-open text-4xl mb-4"></i>
                            <p>Aucun message trouvé</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($messages->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $messages->links() }}
        </div>
    @endif
</div>

<!-- Message Modal -->
<div id="messageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Contenu du message</h3>
            <button onclick="document.getElementById('messageModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <p id="messageContent" class="text-gray-700 whitespace-pre-wrap"></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showMessage(id, content) {
    document.getElementById('messageContent').textContent = content;
    document.getElementById('messageModal').classList.remove('hidden');
}
</script>
@endpush
@endsection
