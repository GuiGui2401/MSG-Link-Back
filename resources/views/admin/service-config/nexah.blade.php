<div class="hidden" id="sms" role="tabpanel" aria-labelledby="sms-tab">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.service-config.update-nexah') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" id="nexah_active" name="is_active"
                               {{ $config && $config->is_active ? 'checked' : '' }}>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Service actif</span>
                    </label>
                </div>

                <div class="mb-6">
                    <label for="nexah_base_url" class="block mb-2 text-sm font-medium text-gray-900">
                        URL de base <span class="text-red-600">*</span>
                    </label>
                    <input type="url" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('nexah_base_url') border-red-500 @enderror"
                           id="nexah_base_url" name="nexah_base_url"
                           value="{{ old('nexah_base_url', $config->nexah_base_url ?? '') }}"
                           placeholder="https://api.nexah.net" required>
                    @error('nexah_base_url')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">URL de base de l'API Nexah</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="nexah_send_endpoint" class="block mb-2 text-sm font-medium text-gray-900">
                            Endpoint d'envoi <span class="text-red-600">*</span>
                        </label>
                        <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('nexah_send_endpoint') border-red-500 @enderror"
                               id="nexah_send_endpoint" name="nexah_send_endpoint"
                               value="{{ old('nexah_send_endpoint', $config->nexah_send_endpoint ?? '/sms/1/text/single') }}"
                               placeholder="/sms/1/text/single" required>
                        @error('nexah_send_endpoint')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nexah_credits_endpoint" class="block mb-2 text-sm font-medium text-gray-900">
                            Endpoint crédits <span class="text-red-600">*</span>
                        </label>
                        <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('nexah_credits_endpoint') border-red-500 @enderror"
                               id="nexah_credits_endpoint" name="nexah_credits_endpoint"
                               value="{{ old('nexah_credits_endpoint', $config->nexah_credits_endpoint ?? '/account/1/balance') }}"
                               placeholder="/account/1/balance" required>
                        @error('nexah_credits_endpoint')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="nexah_user" class="block mb-2 text-sm font-medium text-gray-900">
                            Utilisateur <span class="text-red-600">*</span>
                        </label>
                        <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('nexah_user') border-red-500 @enderror"
                               id="nexah_user" name="nexah_user"
                               value="{{ old('nexah_user', $config->nexah_user ?? '') }}"
                               placeholder="votre_username" required>
                        @error('nexah_user')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nexah_password" class="block mb-2 text-sm font-medium text-gray-900">
                            Mot de passe <span class="text-red-600">*</span>
                        </label>
                        <div class="flex">
                            <input type="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('nexah_password') border-red-500 @enderror"
                                   id="nexah_password" name="nexah_password"
                                   value="{{ old('nexah_password', $config->nexah_password ?? '') }}"
                                   placeholder="••••••••" required>
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500" type="button" onclick="togglePassword('nexah_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('nexah_password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label for="nexah_sender_id" class="block mb-2 text-sm font-medium text-gray-900">
                        Sender ID <span class="text-red-600">*</span>
                    </label>
                    <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('nexah_sender_id') border-red-500 @enderror"
                           id="nexah_sender_id" name="nexah_sender_id"
                           value="{{ old('nexah_sender_id', $config->nexah_sender_id ?? '') }}"
                           placeholder="ENTREPRISE" required maxlength="11">
                    @error('nexah_sender_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">Nom de l'expéditeur (max 11 caractères alphanumériques)</p>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        <i class="fas fa-save mr-2"></i> Sauvegarder
                    </button>
                    <button type="button" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-300" onclick="openNexahTestModal()">
                        <i class="fas fa-paper-plane mr-2"></i> Envoyer un test SMS
                    </button>
                </div>
            </form>

            <!-- Modal de test Nexah SMS -->
            <div id="nexahTestModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50 hidden" style="display: none;">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 mt-20">
                    <div class="flex items-center justify-between p-5 border-b border-gray-200">
                        <h5 class="text-xl font-semibold text-gray-900">Test Nexah SMS - Envoyer un message</h5>
                        <button type="button" class="text-gray-400 hover:text-gray-900" onclick="closeNexahTestModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-6">
                        <div>
                            <label for="test_phone_nexah" class="block mb-2 text-sm font-medium text-gray-900">Numéro de téléphone</label>
                            <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" id="test_phone_nexah" placeholder="237658895572">
                            <p class="mt-2 text-sm text-gray-500">Format: 237XXXXXXXXX (sans +)</p>
                        </div>
                        <div>
                            <label for="test_message_nexah" class="block mb-2 text-sm font-medium text-gray-900">Message</label>
                            <textarea class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" id="test_message_nexah" rows="3" placeholder="Message de test depuis Estuaire Emploie">Ceci est un message de test depuis Estuaire Emploie. Si vous recevez ce message, la configuration Nexah SMS fonctionne correctement!</textarea>
                        </div>
                        <div id="nexah_test_result" class="hidden"></div>
                    </div>
                    <div class="flex items-center justify-between p-6 border-t border-gray-200">
                        <button type="button" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200" onclick="closeNexahTestModal()">
                            Annuler
                        </button>
                        <button type="button" class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300" onclick="sendNexahTest()">
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
                        <i class="fas fa-info-circle mr-2"></i> Aide Nexah SMS
                    </h5>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Configuration requise:</p>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li>Compte Nexah actif</li>
                                <li>Crédits SMS suffisants</li>
                                <li>Sender ID approuvé</li>
                            </ul>
                        </div>
                        <hr class="border-gray-200">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Informations importantes:</p>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li><strong>Base URL:</strong> Fournie par Nexah</li>
                                <li><strong>User/Password:</strong> Credentials API Nexah</li>
                                <li><strong>Sender ID:</strong> Doit être enregistré et approuvé</li>
                            </ul>
                        </div>
                        <hr class="border-gray-200">
                        <div>
                            <p class="text-sm text-gray-700">
                                <strong>Test de connexion:</strong><br>
                                Le bouton "Tester" vérifie vos credentials et affiche votre solde de crédits SMS.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
