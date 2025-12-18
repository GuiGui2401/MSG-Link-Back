<div class="hidden" id="preferences" role="tabpanel" aria-labelledby="preferences-tab">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.service-config.update-preferences') }}" method="POST">
                @csrf
                @method('PUT')

                <h5 class="text-lg font-semibold text-gray-900 mb-4">Préférences de Notification</h5>

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
                    <p class="text-gray-600 mb-6">
                        Choisissez le canal par défaut pour l'envoi des notifications (OTP, alertes, etc.)
                    </p>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-900 mb-3">
                            Canal par défaut <span class="text-red-600">*</span>
                        </label>

                        <div class="flex items-start p-4 mb-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input class="w-4 h-4 mt-1 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 focus:ring-2"
                                   type="radio"
                                   name="default_notification_channel"
                                   id="channel_whatsapp"
                                   value="whatsapp"
                                   {{ \App\Models\ServiceConfiguration::getDefaultNotificationChannel() === 'whatsapp' ? 'checked' : '' }}>
                            <label class="ml-3 flex items-start cursor-pointer" for="channel_whatsapp">
                                <i class="fab fa-whatsapp text-green-500 text-2xl mr-3"></i>
                                <div class="flex-1">
                                    <div class="text-base font-semibold text-gray-900">WhatsApp</div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        Messages instantanés via WhatsApp Business API
                                        @php
                                            $whatsappConfig = \App\Models\ServiceConfiguration::getWhatsAppConfig();
                                        @endphp
                                        @if($whatsappConfig && $whatsappConfig->isConfigured())
                                            <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Configuré</span>
                                        @else
                                            <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Non configuré</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-start p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input class="w-4 h-4 mt-1 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 focus:ring-2"
                                   type="radio"
                                   name="default_notification_channel"
                                   id="channel_sms"
                                   value="sms"
                                   {{ \App\Models\ServiceConfiguration::getDefaultNotificationChannel() === 'sms' ? 'checked' : '' }}>
                            <label class="ml-3 flex items-start cursor-pointer" for="channel_sms">
                                <i class="fas fa-sms text-blue-500 text-2xl mr-3"></i>
                                <div class="flex-1">
                                    <div class="text-base font-semibold text-gray-900">SMS (Nexah)</div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        Messages SMS traditionnels via Nexah
                                        @php
                                            $nexahConfig = \App\Models\ServiceConfiguration::getNexahConfig();
                                        @endphp
                                        @if($nexahConfig && $nexahConfig->isConfigured())
                                            <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Configuré</span>
                                        @else
                                            <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Non configuré</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle mt-0.5 mr-2"></i>
                            <div>
                                <strong>Note:</strong> Si le canal par défaut n'est pas disponible ou configuré, le système basculera automatiquement sur l'autre canal.
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        <i class="fas fa-save mr-2"></i> Sauvegarder les préférences
                    </button>
                </div>
            </form>
        </div>

        <div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6">
                    <h5 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-chart-line mr-2"></i> Comparaison des canaux
                    </h5>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Critère</th>
                                    <th scope="col" class="px-4 py-3 text-center">WhatsApp</th>
                                    <th scope="col" class="px-4 py-3 text-center">SMS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-3 text-sm">Vitesse</td>
                                    <td class="px-4 py-3 text-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-3 text-sm">Coût</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs text-green-600 font-medium">Faible</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs text-yellow-600 font-medium">Moyen</span>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-3 text-sm">Taux de délivrabilité</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs text-green-600 font-medium">~98%</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs text-green-600 font-medium">~95%</span>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-3 text-sm">Interactivité</td>
                                    <td class="px-4 py-3 text-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <i class="fas fa-times text-red-600"></i>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3 text-sm">Format riche</td>
                                    <td class="px-4 py-3 text-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <i class="fas fa-times text-red-600"></i>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr class="my-4 border-gray-200">

                    <div>
                        <h6 class="text-base font-semibold text-gray-900 mb-3">Recommandations:</h6>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-2">
                            <li><strong>WhatsApp:</strong> Idéal pour les OTP et notifications fréquentes (coût réduit)</li>
                            <li><strong>SMS:</strong> Plus universel, fonctionne sans Internet</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
