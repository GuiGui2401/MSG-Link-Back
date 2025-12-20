@extends('admin.layouts.app')

@section('title', 'Configuration Paiements')
@section('header', 'Configuration des Paiements')

@section('content')
<div class="max-w-6xl">
    <!-- Introduction -->
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Configuration des providers de paiement</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Choisissez le provider de paiement à utiliser pour chaque type de transaction.</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Dépôt :</strong> CinetPay ou LygosApp (au choix)</li>
                        <li><strong>Retrait :</strong> Uniquement CinetPay</li>
                        <li><strong>Cadeaux & Premium :</strong> CinetPay ou LygosApp</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.payment-config.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Dépôts Wallet -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-green-50 to-white">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-download text-green-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-800">Dépôts Wallet</h3>
                            <p class="text-xs text-gray-500">Rechargement du portefeuille</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($providers as $key => $provider)
                            @if(in_array('deposit', $provider['supports']))
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-{{ $provider['color'] }}-300 {{ ($configs->get('deposit_provider')->value ?? 'ligosapp') === $key ? 'border-'.$provider['color'].'-500 bg-'.$provider['color'].'-50' : 'border-gray-200' }}">
                                    <input type="radio"
                                           name="deposit_provider"
                                           value="{{ $key }}"
                                           {{ ($configs->get('deposit_provider')->value ?? 'ligosapp') === $key ? 'checked' : '' }}
                                           class="w-4 h-4 text-{{ $provider['color'] }}-600">
                                    <div class="ml-3 flex items-center flex-1">
                                        <div class="w-10 h-10 bg-{{ $provider['color'] }}-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-{{ $provider['icon'] }} text-{{ $provider['color'] }}-600"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="font-medium text-gray-900">{{ $provider['name'] }}</p>
                                            <p class="text-xs text-gray-500">Paiement Mobile Money</p>
                                        </div>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Retraits Wallet -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-white">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-upload text-red-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-800">Retraits Wallet</h3>
                            <p class="text-xs text-gray-500">Transfert vers Mobile Money</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($providers as $key => $provider)
                            @if(in_array('withdrawal', $provider['supports']))
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all {{ $key === 'cinetpay' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <input type="radio"
                                           name="withdrawal_provider"
                                           value="{{ $key }}"
                                           {{ ($configs->get('withdrawal_provider')->value ?? 'cinetpay') === $key ? 'checked' : '' }}
                                           {{ $key !== 'cinetpay' ? 'disabled' : '' }}
                                           class="w-4 h-4 text-{{ $provider['color'] }}-600">
                                    <div class="ml-3 flex items-center flex-1">
                                        <div class="w-10 h-10 bg-{{ $provider['color'] }}-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-{{ $provider['icon'] }} text-{{ $provider['color'] }}-600"></i>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="font-medium text-gray-900">{{ $provider['name'] }}</p>
                                            @if($key === 'cinetpay')
                                                <p class="text-xs text-blue-600 font-medium">Recommandé pour les retraits</p>
                                            @else
                                                <p class="text-xs text-gray-400">Non disponible pour les retraits</p>
                                            @endif
                                        </div>
                                        @if($key === 'cinetpay')
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">Requis</span>
                                        @endif
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Paiements Cadeaux -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-pink-50 to-white">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-gift text-pink-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-800">Paiements Cadeaux</h3>
                            <p class="text-xs text-gray-500">Achat de cadeaux virtuels</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($providers as $key => $provider)
                            @if(in_array('gift', $provider['supports']))
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-{{ $provider['color'] }}-300 {{ ($configs->get('gift_provider')->value ?? 'cinetpay') === $key ? 'border-'.$provider['color'].'-500 bg-'.$provider['color'].'-50' : 'border-gray-200' }}">
                                    <input type="radio"
                                           name="gift_provider"
                                           value="{{ $key }}"
                                           {{ ($configs->get('gift_provider')->value ?? 'cinetpay') === $key ? 'checked' : '' }}
                                           class="w-4 h-4 text-{{ $provider['color'] }}-600">
                                    <div class="ml-3 flex items-center flex-1">
                                        <div class="w-10 h-10 bg-{{ $provider['color'] }}-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-{{ $provider['icon'] }} text-{{ $provider['color'] }}-600"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="font-medium text-gray-900">{{ $provider['name'] }}</p>
                                            <p class="text-xs text-gray-500">Paiement Mobile Money</p>
                                        </div>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Abonnements Premium -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-yellow-50 to-white">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-crown text-yellow-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-800">Abonnements Premium</h3>
                            <p class="text-xs text-gray-500">Souscription premium</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach($providers as $key => $provider)
                            @if(in_array('premium', $provider['supports']))
                                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-{{ $provider['color'] }}-300 {{ ($configs->get('premium_provider')->value ?? 'cinetpay') === $key ? 'border-'.$provider['color'].'-500 bg-'.$provider['color'].'-50' : 'border-gray-200' }}">
                                    <input type="radio"
                                           name="premium_provider"
                                           value="{{ $key }}"
                                           {{ ($configs->get('premium_provider')->value ?? 'cinetpay') === $key ? 'checked' : '' }}
                                           class="w-4 h-4 text-{{ $provider['color'] }}-600">
                                    <div class="ml-3 flex items-center flex-1">
                                        <div class="w-10 h-10 bg-{{ $provider['color'] }}-100 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-{{ $provider['icon'] }} text-{{ $provider['color'] }}-600"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="font-medium text-gray-900">{{ $provider['name'] }}</p>
                                            <p class="text-xs text-gray-500">Paiement Mobile Money</p>
                                        </div>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.dashboard') }}"
               class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-times mr-2"></i>Annuler
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Enregistrer les modifications
            </button>
        </div>
    </form>

    <!-- Providers Status -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-server text-gray-500 mr-2"></i>
                Statut des Providers
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- CinetPay -->
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-credit-card text-blue-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">CinetPay</p>
                            </div>
                        </div>
                        @if(!empty(config('cinetpay.api_key')))
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                                <i class="fas fa-check-circle mr-1"></i>Actif
                            </span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                <i class="fas fa-times-circle mr-1"></i>Inactif
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-600 space-y-1">
                        <p><i class="fas fa-check text-green-500 mr-1"></i> Retraits</p>
                        <p><i class="fas fa-check text-green-500 mr-1"></i> Cadeaux</p>
                        <p><i class="fas fa-check text-green-500 mr-1"></i> Premium</p>
                    </div>
                </div>

                <!-- LygosApp -->
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-mobile-alt text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">LygosApp</p>
                            </div>
                        </div>
                        @if(!empty(config('services.ligosapp.api_key')))
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                                <i class="fas fa-check-circle mr-1"></i>Actif
                            </span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                <i class="fas fa-times-circle mr-1"></i>Inactif
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-600 space-y-1">
                        <p><i class="fas fa-check text-green-500 mr-1"></i> Dépôts</p>
                        <p><i class="fas fa-check text-green-500 mr-1"></i> Cadeaux</p>
                        <p><i class="fas fa-check text-green-500 mr-1"></i> Premium</p>
                    </div>
                </div>

                <!-- Intouch -->
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-wallet text-orange-600"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">Intouch</p>
                            </div>
                        </div>
                        @if(!empty(config('services.intouch.api_key')))
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                                <i class="fas fa-check-circle mr-1"></i>Actif
                            </span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">
                                <i class="fas fa-times-circle mr-1"></i>Inactif
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-600 space-y-1">
                        <p><i class="fas fa-times text-gray-300 mr-1"></i> À venir</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
