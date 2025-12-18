<div id="whatsapp" role="tabpanel" aria-labelledby="whatsapp-tab">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.service-config.update-whatsapp') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" id="whatsapp_active" name="is_active"
                               {{ $config && $config->is_active ? 'checked' : '' }}>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Service actif</span>
                    </label>
                </div>

                <div class="mb-6">
                    <label for="whatsapp_api_token" class="block mb-2 text-sm font-medium text-gray-900">
                        API Token <span class="text-red-600">*</span>
                    </label>
                    <div class="flex">
                        <input type="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('whatsapp_api_token') border-red-500 @enderror"
                               id="whatsapp_api_token" name="whatsapp_api_token"
                               value="{{ old('whatsapp_api_token', $config->whatsapp_api_token ?? '') }}"
                               placeholder="EAAxxxxxxxxxx..." required>
                        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500" type="button" onclick="togglePassword('whatsapp_api_token')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('whatsapp_api_token')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">Token d'accès depuis Meta Business Suite</p>
                </div>

                <div class="mb-6">
                    <label for="whatsapp_phone_number_id" class="block mb-2 text-sm font-medium text-gray-900">
                        Phone Number ID <span class="text-red-600">*</span>
                    </label>
                    <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('whatsapp_phone_number_id') border-red-500 @enderror"
                           id="whatsapp_phone_number_id" name="whatsapp_phone_number_id"
                           value="{{ old('whatsapp_phone_number_id', $config->whatsapp_phone_number_id ?? '') }}"
                           placeholder="123456789012345" required>
                    @error('whatsapp_phone_number_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">ID du numéro de téléphone WhatsApp Business</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="whatsapp_api_version" class="block mb-2 text-sm font-medium text-gray-900">
                            Version API <span class="text-red-600">*</span>
                        </label>
                        <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('whatsapp_api_version') border-red-500 @enderror"
                               id="whatsapp_api_version" name="whatsapp_api_version"
                               value="{{ old('whatsapp_api_version', $config->whatsapp_api_version ?? 'v21.0') }}"
                               placeholder="v21.0" required>
                        @error('whatsapp_api_version')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="whatsapp_language" class="block mb-2 text-sm font-medium text-gray-900">
                            Langue du template <span class="text-red-600">*</span>
                        </label>
                        <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('whatsapp_language') border-red-500 @enderror"
                               id="whatsapp_language" name="whatsapp_language"
                               value="{{ old('whatsapp_language', $config->whatsapp_language ?? 'fr') }}"
                               placeholder="fr" required>
                        @error('whatsapp_language')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500">Ex: fr, en, fr_FR, en_US</p>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="whatsapp_template_name" class="block mb-2 text-sm font-medium text-gray-900">
                        Nom du template <span class="text-red-600">*</span>
                    </label>
                    <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('whatsapp_template_name') border-red-500 @enderror"
                           id="whatsapp_template_name" name="whatsapp_template_name"
                           value="{{ old('whatsapp_template_name', $config->whatsapp_template_name ?? '') }}"
                           placeholder="otp_verification" required>
                    @error('whatsapp_template_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">Nom du template approuvé dans WhatsApp Business Manager</p>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        <i class="fas fa-save mr-2"></i> Sauvegarder
                    </button>
                    <button type="button" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-300" onclick="openWhatsAppTestModal()">
                        <i class="fas fa-paper-plane mr-2"></i> Envoyer un test OTP
                    </button>
                </div>
            </form>

            <!-- Modal de test WhatsApp -->
            <div id="whatsappTestModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50 hidden" style="display: none;">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 mt-20">
                    <div class="flex items-center justify-between p-5 border-b border-gray-200">
                        <h5 class="text-xl font-semibold text-gray-900">Test WhatsApp - Envoyer un OTP</h5>
                        <button type="button" class="text-gray-400 hover:text-gray-900" onclick="closeWhatsAppTestModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-6">
                        <div>
                            <label for="test_phone_whatsapp" class="block mb-2 text-sm font-medium text-gray-900">Numéro de téléphone</label>
                            <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" id="test_phone_whatsapp" placeholder="+237658895572">
                            <p class="mt-2 text-sm text-gray-500">Format: +237XXXXXXXXX ou +243XXXXXXXXX</p>
                        </div>
                        <div>
                            <label for="test_otp_whatsapp" class="block mb-2 text-sm font-medium text-gray-900">Code OTP (optionnel)</label>
                            <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" id="test_otp_whatsapp" placeholder="123456">
                            <p class="mt-2 text-sm text-gray-500">Laissez vide pour générer automatiquement</p>
                        </div>
                        <div id="whatsapp_test_result" class="hidden"></div>
                    </div>
                    <div class="flex items-center justify-between p-6 border-t border-gray-200">
                        <button type="button" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200" onclick="closeWhatsAppTestModal()">
                            Annuler
                        </button>
                        <button type="button" class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300" onclick="sendWhatsAppTest()">
                            <i class="fas fa-paper-plane mr-2"></i> Envoyer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6">
                    <h5 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i> Aide WhatsApp
                    </h5>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Configuration requise:</p>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li>Compte Meta Business</li>
                                <li>WhatsApp Business API</li>
                                <li>Template de message approuvé</li>
                                <li>Numéro WhatsApp vérifié</li>
                            </ul>
                        </div>
                        <hr class="border-gray-200">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Où trouver ces informations:</p>
                            <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1">
                                <li>Connectez-vous à <a href="https://business.facebook.com" target="_blank" class="text-blue-600 hover:underline">Meta Business Suite</a></li>
                                <li>Accédez à WhatsApp Manager</li>
                                <li>Sélectionnez votre compte WhatsApp Business</li>
                                <li>API Token: Paramètres → API Token</li>
                                <li>Phone Number ID: Numéros de téléphone</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
