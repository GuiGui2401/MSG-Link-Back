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
                   placeholder="Rechercher par nom d'utilisateur..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
        </div>
        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
            <option value="">Toutes les conversations</option>
            <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Avec messages non lus</option>
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

<!-- Messagerie Interface (Split View) -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="flex" style="height: 600px;">
        <!-- Liste des conversations (Gauche) -->
        <div class="w-1/3 border-r border-gray-200 overflow-y-auto">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 uppercase">Conversations</h3>
            </div>

            @forelse($conversations as $conversation)
                @php
                    $isActive = $selectedConversation &&
                                $selectedConversation['user1']->id == $conversation->user1_id &&
                                $selectedConversation['user2']->id == $conversation->user2_id;
                @endphp
                <a href="{{ route('admin.messages.index', ['user1' => $conversation->user1_id, 'user2' => $conversation->user2_id] + request()->only(['search', 'status'])) }}"
                   class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition {{ $isActive ? 'bg-blue-50 border-l-4 border-l-blue-500' : '' }}">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center flex-1 min-w-0">
                            <!-- Avatars des deux utilisateurs -->
                            <div class="flex -space-x-2 mr-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center border-2 border-white">
                                    <span class="text-sm font-medium text-purple-600">{{ $conversation->user1->initial ?? '?' }}</span>
                                </div>
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center border-2 border-white">
                                    <span class="text-sm font-medium text-blue-600">{{ $conversation->user2->initial ?? '?' }}</span>
                                </div>
                            </div>

                            <!-- Noms des utilisateurs -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $conversation->user1->username ?? 'Utilisateur supprimé' }}
                                    <i class="fas fa-exchange-alt text-xs text-gray-400 mx-1"></i>
                                    {{ $conversation->user2->username ?? 'Utilisateur supprimé' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $conversation->message_count }} message{{ $conversation->message_count > 1 ? 's' : '' }}
                                </p>
                            </div>
                        </div>

                        <!-- Badge non lus + Date -->
                        <div class="ml-2 text-right flex-shrink-0">
                            @if($conversation->unread_count > 0)
                                <span class="inline-block px-2 py-1 text-xs font-bold rounded-full bg-red-500 text-white">
                                    {{ $conversation->unread_count }}
                                </span>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-comments text-4xl mb-4"></i>
                    <p>Aucune conversation trouvée</p>
                </div>
            @endforelse

            <!-- Pagination -->
            @if($conversations->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $conversations->links() }}
                </div>
            @endif
        </div>

        <!-- Détail de la conversation (Droite) -->
        <div class="flex-1 flex flex-col">
            @if($selectedConversation)
                <!-- En-tête de la conversation -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex -space-x-2 mr-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center border-2 border-white">
                                    <span class="text-lg font-medium text-purple-600">{{ $selectedConversation['user1']->initial }}</span>
                                </div>
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center border-2 border-white">
                                    <span class="text-lg font-medium text-blue-600">{{ $selectedConversation['user2']->initial }}</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $selectedConversation['user1']->username }}
                                    <i class="fas fa-exchange-alt text-sm text-gray-400 mx-2"></i>
                                    {{ $selectedConversation['user2']->username }}
                                </h3>
                                <p class="text-sm text-gray-500">{{ $conversationMessages->count() }} messages</p>
                            </div>
                        </div>

                        <a href="{{ route('admin.messages.index') }}" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Messages de la conversation -->
                <div class="flex-1 overflow-y-auto p-6 bg-gray-50" id="messagesContainer">
                    @forelse($conversationMessages as $message)
                        <div class="mb-4 {{ $message->sender_id == $selectedConversation['user1']->id ? 'text-left' : 'text-right' }}">
                            <div class="inline-block max-w-[70%]">
                                <!-- Info expéditeur -->
                                <div class="flex items-center mb-1 {{ $message->sender_id == $selectedConversation['user1']->id ? '' : 'flex-row-reverse' }}">
                                    <div class="w-8 h-8 {{ $message->sender_id == $selectedConversation['user1']->id ? 'bg-purple-100 mr-2' : 'bg-blue-100 ml-2' }} rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium {{ $message->sender_id == $selectedConversation['user1']->id ? 'text-purple-600' : 'text-blue-600' }}">
                                            {{ $message->sender->initial }}
                                        </span>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700">{{ $message->sender->username }}</span>
                                    <span class="text-xs text-gray-400 mx-2">•</span>
                                    <span class="text-xs text-gray-400">{{ $message->created_at->format('d/m/Y H:i') }}</span>

                                    <!-- Badge type de message -->
                                    @if(isset($message->message_type))
                                        <span class="ml-2 px-2 py-0.5 text-xs rounded {{ $message->message_type == 'anonymous' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $message->message_type == 'anonymous' ? 'Anonyme' : 'Chat' }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Bulle de message -->
                                <div class="px-4 py-3 rounded-lg {{ $message->sender_id == $selectedConversation['user1']->id ? 'bg-white border border-gray-200' : 'bg-blue-600 text-white' }}">
                                    <p class="text-sm whitespace-pre-wrap break-words">{{ $message->content }}</p>

                                    <!-- Statut de lecture -->
                                    @if(isset($message->message_type) && $message->message_type == 'anonymous')
                                        @if($message->read_at)
                                            <p class="text-xs mt-2 {{ $message->sender_id == $selectedConversation['user1']->id ? 'text-gray-400' : 'text-blue-200' }}">
                                                <i class="fas fa-check-double"></i> Lu le {{ $message->read_at->format('d/m/Y à H:i') }}
                                            </p>
                                        @else
                                            <p class="text-xs mt-2 {{ $message->sender_id == $selectedConversation['user1']->id ? 'text-gray-400' : 'text-blue-200' }}">
                                                <i class="fas fa-check"></i> Non lu
                                            </p>
                                        @endif
                                    @elseif(isset($message->message_type) && $message->message_type == 'chat')
                                        @if($message->is_read)
                                            <p class="text-xs mt-2 {{ $message->sender_id == $selectedConversation['user1']->id ? 'text-gray-400' : 'text-blue-200' }}">
                                                <i class="fas fa-check-double"></i> Lu {{ $message->read_at ? 'le ' . $message->read_at->format('d/m/Y à H:i') : '' }}
                                            </p>
                                        @else
                                            <p class="text-xs mt-2 {{ $message->sender_id == $selectedConversation['user1']->id ? 'text-gray-400' : 'text-blue-200' }}">
                                                <i class="fas fa-check"></i> Non lu
                                            </p>
                                        @endif
                                    @endif
                                </div>

                                <!-- Actions admin -->
                                <div class="mt-1 {{ $message->sender_id == $selectedConversation['user1']->id ? 'text-left' : 'text-right' }}">
                                    @if(isset($message->message_type) && $message->message_type == 'anonymous')
                                        <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash mr-1"></i>Supprimer
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Message de chat (ID: {{ $message->id }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-500 py-12">
                            <i class="fas fa-comment-slash text-4xl mb-4"></i>
                            <p>Aucun message dans cette conversation</p>
                        </div>
                    @endforelse
                </div>
            @else
                <!-- Placeholder quand aucune conversation n'est sélectionnée -->
                <div class="flex-1 flex items-center justify-center bg-gray-50">
                    <div class="text-center text-gray-400">
                        <i class="fas fa-comments text-6xl mb-4"></i>
                        <p class="text-lg font-medium">Sélectionnez une conversation</p>
                        <p class="text-sm">Cliquez sur une conversation à gauche pour voir les messages</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-scroll vers le bas des messages
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});
</script>
@endpush
@endsection
