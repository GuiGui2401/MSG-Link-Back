@extends('admin.layouts.app')

@section('title', 'Paramètres')
@section('header', 'Paramètres')

@section('content')
<div class="max-w-4xl">
    <!-- General Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-cog text-gray-500 mr-2"></i>
                Paramètres généraux
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'application</label>
                    <input type="text" value="{{ config('app.name') }}" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Environnement</label>
                    <input type="text" value="{{ config('app.env') }}" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Frontend</label>
                    <input type="text" value="{{ config('msglink.urls.frontend', 'Non configuré') }}" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Version API</label>
                    <input type="text" value="1.0.0" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-percentage text-green-500 mr-2"></i>
                Commissions
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Commission cadeaux (%)</label>
                    <input type="number" value="{{ config('msglink.commission.gifts', 20) }}" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Pourcentage prélevé sur chaque cadeau</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Frais de retrait (%)</label>
                    <input type="number" value="{{ config('msglink.withdrawal.fee_percent', 5) }}" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                    <p class="text-xs text-gray-500 mt-1">Frais appliqués lors des retraits</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Retrait minimum (FCFA)</label>
                    <input type="number" value="{{ config('msglink.withdrawal.minimum', 1000) }}" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Retrait maximum (FCFA)</label>
                    <input type="number" value="{{ config('msglink.withdrawal.maximum', 500000) }}" disabled
                           class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500">
                </div>
            </div>
            <p class="mt-4 text-sm text-yellow-600 bg-yellow-50 px-4 py-2 rounded-lg">
                <i class="fas fa-info-circle mr-1"></i>
                Ces paramètres sont configurés via les fichiers de configuration. Modifiez-les dans <code>config/msglink.php</code>.
            </p>
        </div>
    </div>

    <!-- Payment Providers -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-credit-card text-blue-500 mr-2"></i>
                Providers de paiement
            </h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <!-- CinetPay -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-credit-card text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">CinetPay</p>
                            <p class="text-sm text-gray-500">Paiements Mobile Money</p>
                        </div>
                    </div>
                    @if(!empty(config('services.cinetpay.api_key')))
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Configuré</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">À configurer</span>
                    @endif
                </div>

                <!-- LigosApp -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-mobile-alt text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">LigosApp</p>
                            <p class="text-sm text-gray-500">Paiements alternatifs</p>
                        </div>
                    </div>
                    @if(!empty(config('services.ligosapp.api_key')))
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Configuré</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">À configurer</span>
                    @endif
                </div>

                <!-- Intouch -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-orange-600"></i>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Intouch</p>
                            <p class="text-sm text-gray-500">Agrégateur de paiement</p>
                        </div>
                    </div>
                    @if(!empty(config('services.intouch.api_key')))
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">Configuré</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">À configurer</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-server text-purple-500 mr-2"></i>
                Informations système
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Version PHP</p>
                    <p class="font-medium text-gray-900">{{ PHP_VERSION }}</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Version Laravel</p>
                    <p class="font-medium text-gray-900">{{ app()->version() }}</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Driver Base de données</p>
                    <p class="font-medium text-gray-900">{{ config('database.default') }}</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Driver Cache</p>
                    <p class="font-medium text-gray-900">{{ config('cache.default') }}</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Driver Queue</p>
                    <p class="font-medium text-gray-900">{{ config('queue.default') }}</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500">Driver Session</p>
                    <p class="font-medium text-gray-900">{{ config('session.driver') }}</p>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="font-medium text-gray-800 mb-3">Actions de maintenance</h4>
                <div class="flex flex-wrap gap-3">
                    <form action="{{ route('admin.cache.clear') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                            <i class="fas fa-broom mr-2"></i>Vider le cache
                        </button>
                    </form>
                    <form action="{{ route('admin.cache.config') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors text-sm">
                            <i class="fas fa-sync mr-2"></i>Recharger la config
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
