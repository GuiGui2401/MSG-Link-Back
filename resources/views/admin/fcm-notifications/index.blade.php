@extends('admin.layouts.app')

@section('title', 'Annonces Globales FCM')
@section('header', 'Annonces Globales via FCM')

@section('content')
<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes pulse-glow {
        0%, 100% {
            box-shadow: 0 0 20px rgba(192, 38, 211, 0.3);
        }
        50% {
            box-shadow: 0 0 40px rgba(192, 38, 211, 0.6);
        }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out;
    }

    .animate-slideInRight {
        animation: slideInRight 0.6s ease-out;
    }

    .pulse-glow {
        animation: pulse-glow 2s ease-in-out infinite;
    }

    .char-counter {
        transition: all 0.3s ease;
    }

    .char-counter.warning {
        color: #f59e0b;
    }

    .char-counter.danger {
        color: #ef4444;
    }
</style>

<!-- Alert Messages -->
@if(session('success'))
    <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 px-6 py-4 rounded-xl shadow-lg animate-fadeInUp">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-2xl text-green-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-bold text-lg">Succès !</p>
                <p class="text-sm">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="mb-6 bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-xl shadow-lg animate-fadeInUp">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
            </div>
            <div class="ml-4">
                <p class="font-bold text-lg">Erreur !</p>
                <p class="text-sm">{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Users -->
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl shadow-lg border border-blue-200 p-6 animate-fadeInUp" style="animation-delay: 0.1s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-1">Total utilisateurs</p>
                <p class="text-4xl font-bold text-blue-900">{{ number_format($stats['total_users'] ?? 0) }}</p>
            </div>
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-users text-2xl text-white"></i>
            </div>
        </div>
    </div>

    <!-- Users with FCM -->
    <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-2xl shadow-lg border border-green-200 p-6 animate-fadeInUp" style="animation-delay: 0.2s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-green-600 uppercase tracking-wide mb-1">Destinataires actifs</p>
                <p class="text-4xl font-bold text-green-900">{{ number_format($stats['with_fcm'] ?? 0) }}</p>
                <p class="text-xs text-green-600 mt-1">recevront la notification</p>
            </div>
            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-bell text-2xl text-white"></i>
            </div>
        </div>
    </div>

    <!-- Coverage -->
    <div class="bg-gradient-to-br from-purple-50 to-violet-100 rounded-2xl shadow-lg border border-purple-200 p-6 animate-fadeInUp" style="animation-delay: 0.3s">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-purple-600 uppercase tracking-wide mb-1">Taux de couverture</p>
                <p class="text-4xl font-bold text-purple-900">{{ number_format($stats['percentage_with_fcm'] ?? 0, 1) }}%</p>
                <p class="text-xs text-purple-600 mt-1">des utilisateurs</p>
            </div>
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-chart-pie text-2xl text-white"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Formulaire d'envoi -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden animate-slideInRight" style="animation-delay: 0.4s">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-600 via-purple-600 to-primary-700 px-8 py-6">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-bullhorn text-2xl text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Nouvelle Annonce Globale</h2>
                        <p class="text-primary-100 text-sm mt-1">Envoyer une notification à tous les utilisateurs</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form action="{{ route('admin.fcm-notifications.send') }}" method="POST" class="p-8">
                @csrf

                <!-- Titre -->
                <div class="mb-6">
                    <label for="title" class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-heading text-primary-600 mr-2"></i>
                        Titre de l'annonce
                    </label>
                    <div class="relative">
                        <input type="text"
                               name="title"
                               id="title"
                               maxlength="100"
                               required
                               class="w-full px-5 py-4 pl-12 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300 text-lg font-medium"
                               placeholder="Ex: Nouvelle fonctionnalité disponible !"
                               oninput="updateCharCount('title', 100)">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-font text-lg"></i>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <p class="text-xs text-gray-500">Le titre sera affiché en gras dans la notification</p>
                        <span id="title-counter" class="text-xs font-semibold text-gray-500 char-counter">0/100</span>
                    </div>
                    @error('title')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Message -->
                <div class="mb-8">
                    <label for="message" class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-comment-dots text-primary-600 mr-2"></i>
                        Message de l'annonce
                    </label>
                    <textarea name="message"
                              id="message"
                              rows="6"
                              maxlength="500"
                              required
                              class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300 resize-none"
                              placeholder="Écrivez votre message ici... Soyez clair et concis pour que les utilisateurs comprennent rapidement."
                              oninput="updateCharCount('message', 500)"></textarea>
                    <div class="flex justify-between items-center mt-2">
                        <p class="text-xs text-gray-500">Le message sera affiché sous le titre</p>
                        <span id="message-counter" class="text-xs font-semibold text-gray-500 char-counter">0/500</span>
                    </div>
                    @error('message')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Preview -->
                <div class="mb-8 p-6 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border-2 border-dashed border-gray-300">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">
                        <i class="fas fa-eye mr-2"></i>Aperçu de la notification
                    </p>
                    <div class="bg-white rounded-lg shadow-lg p-4 max-w-sm">
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-comment-dots text-white"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 mb-1" id="preview-title">Titre de l'annonce</p>
                                <p class="text-xs text-gray-600" id="preview-message">Le message apparaîtra ici...</p>
                                <p class="text-xs text-gray-400 mt-2">Weylo • Maintenant</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info avant envoi -->
                <div class="mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-database text-blue-600 text-lg mt-0.5"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-blue-900">Sauvegarde automatique</p>
                            <p class="text-xs text-blue-700 mt-1">
                                Cette notification sera <strong>push FCM</strong> ET sauvegardée dans la table <code class="bg-blue-100 px-1 py-0.5 rounded">notifications</code> pour être visible dans l'historique de chaque utilisateur.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-6 border-t border-gray-200">
                    <div class="flex flex-col space-y-1">
                        <div class="flex items-center text-sm font-semibold text-gray-700">
                            <i class="fas fa-users text-green-500 mr-2"></i>
                            <span><strong class="text-green-600">{{ number_format($stats['with_fcm'] ?? 0) }}</strong> destinataires</span>
                        </div>
                        <div class="flex items-center text-xs text-gray-500">
                            <i class="fas fa-bell mr-2"></i>
                            <span>Push + Base de données</span>
                        </div>
                    </div>
                    <button type="submit"
                            class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 text-white rounded-xl font-bold text-base hover:shadow-2xl hover:-translate-y-0.5 transition-all duration-300 pulse-glow">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Envoyer la notification
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Informations et conseils -->
    <div class="lg:col-span-1">
        <div class="space-y-6">
            <!-- Info Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl shadow-lg border border-blue-200 p-6 animate-fadeInUp" style="animation-delay: 0.5s">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-info-circle text-white"></i>
                    </div>
                    <h3 class="ml-3 text-lg font-bold text-blue-900">Comment ça marche ?</h3>
                </div>
                <ul class="space-y-3 text-sm text-blue-800">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                        <span>Envoi via le topic FCM <strong>"global_announcements"</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                        <span>Tous les utilisateurs <strong>avec FCM token</strong> la recevront <strong>une seule fois</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                        <span>La notification apparaît même si l'app est <strong>fermée</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                        <span>Sauvegardée dans l'<strong>historique des notifications</strong> de chaque utilisateur</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-3"></i>
                        <span>Les logs sont enregistrés pour le <strong>suivi</strong></span>
                    </li>
                </ul>
            </div>

            <!-- Best Practices -->
            <div class="bg-gradient-to-br from-purple-50 to-violet-100 rounded-2xl shadow-lg border border-purple-200 p-6 animate-fadeInUp" style="animation-delay: 0.6s">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-lightbulb text-white"></i>
                    </div>
                    <h3 class="ml-3 text-lg font-bold text-purple-900">Bonnes pratiques</h3>
                </div>
                <ul class="space-y-3 text-sm text-purple-800">
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-purple-600 mt-0.5 mr-3"></i>
                        <span>Utilisez un <strong>titre court</strong> et accrocheur (max 50 caractères)</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-purple-600 mt-0.5 mr-3"></i>
                        <span>Soyez <strong>clair et concis</strong> dans le message</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-purple-600 mt-0.5 mr-3"></i>
                        <span>Évitez d'envoyer trop de notifications (<strong>spam</strong>)</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-purple-600 mt-0.5 mr-3"></i>
                        <span>Testez avec un petit groupe avant un envoi <strong>massif</strong></span>
                    </li>
                </ul>
            </div>

            <!-- Warning Card -->
            <div class="bg-gradient-to-br from-orange-50 to-amber-100 rounded-2xl shadow-lg border border-orange-200 p-6 animate-fadeInUp" style="animation-delay: 0.7s">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-white"></i>
                    </div>
                    <h3 class="ml-3 text-lg font-bold text-orange-900">Attention</h3>
                </div>
                <p class="text-sm text-orange-800">
                    <strong>Une fois envoyée</strong>, la notification ne peut pas être annulée.
                    Assurez-vous que le contenu est correct avant d'envoyer.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction de mise à jour du compteur de caractères
function updateCharCount(fieldId, maxLength) {
    const field = document.getElementById(fieldId);
    const counter = document.getElementById(fieldId + '-counter');
    const length = field.value.length;
    const percentage = (length / maxLength) * 100;

    counter.textContent = `${length}/${maxLength}`;

    // Changer la couleur selon le pourcentage
    counter.classList.remove('warning', 'danger');
    if (percentage >= 90) {
        counter.classList.add('danger');
    } else if (percentage >= 70) {
        counter.classList.add('warning');
    }

    // Mise à jour de l'aperçu
    if (fieldId === 'title') {
        document.getElementById('preview-title').textContent = field.value || 'Titre de l\'annonce';
    } else if (fieldId === 'message') {
        document.getElementById('preview-message').textContent = field.value || 'Le message apparaîtra ici...';
    }
}

// Initialiser les compteurs au chargement
document.addEventListener('DOMContentLoaded', function() {
    updateCharCount('title', 100);
    updateCharCount('message', 500);
});
</script>
@endsection
