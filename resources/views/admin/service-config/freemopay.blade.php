<div class="hidden" id="payment" role="tabpanel" aria-labelledby="payment-tab">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('admin.service-config.update-freemopay') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" id="freemopay_active" name="is_active"
                               {{ $config && $config->is_active ? 'checked' : '' }}>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-gray-900">Service actif</span>
                    </label>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        <div>
                            <strong>Important:</strong> FreeMoPay utilise l'API v2 avec authentification Bearer Token.
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="freemopay_base_url" class="block mb-2 text-sm font-medium text-gray-900">
                        URL de base <span class="text-red-600">*</span>
                    </label>
                    <input type="url" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_base_url') border-red-500 @enderror"
                           id="freemopay_base_url" name="freemopay_base_url"
                           value="{{ old('freemopay_base_url', $config->freemopay_base_url ?? 'https://api-v2.freemopay.com') }}"
                           placeholder="https://api-v2.freemopay.com" required>
                    @error('freemopay_base_url')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="freemopay_app_key" class="block mb-2 text-sm font-medium text-gray-900">
                            App Key <span class="text-red-600">*</span>
                        </label>
                        <div class="flex">
                            <input type="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_app_key') border-red-500 @enderror"
                                   id="freemopay_app_key" name="freemopay_app_key"
                                   value="{{ old('freemopay_app_key', $config->freemopay_app_key ?? '') }}"
                                   placeholder="app_xxxxxxxxxx" required>
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500" type="button" onclick="togglePassword('freemopay_app_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('freemopay_app_key')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="freemopay_secret_key" class="block mb-2 text-sm font-medium text-gray-900">
                            Secret Key <span class="text-red-600">*</span>
                        </label>
                        <div class="flex">
                            <input type="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_secret_key') border-red-500 @enderror"
                                   id="freemopay_secret_key" name="freemopay_secret_key"
                                   value="{{ old('freemopay_secret_key', $config->freemopay_secret_key ?? '') }}"
                                   placeholder="secret_xxxxxxxxxx" required>
                            <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500" type="button" onclick="togglePassword('freemopay_secret_key')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('freemopay_secret_key')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mb-6">
                    <label for="freemopay_callback_url" class="block mb-2 text-sm font-medium text-gray-900">
                        Callback URL <span class="text-red-600">*</span>
                    </label>
                    <input type="url" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_callback_url') border-red-500 @enderror"
                           id="freemopay_callback_url" name="freemopay_callback_url"
                           value="{{ old('freemopay_callback_url', $config->freemopay_callback_url ?? '') }}"
                           placeholder="https://votresite.com/api/webhooks/freemopay" required>
                    @error('freemopay_callback_url')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">
                        URL publique pour recevoir les notifications de paiement (doit être accessible depuis Internet)
                    </p>
                </div>

                <h5 class="text-lg font-semibold text-gray-900 mb-4 mt-8">Paramètres avancés</h5>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="freemopay_init_payment_timeout" class="block mb-2 text-sm font-medium text-gray-900">
                            Timeout init paiement (s)
                        </label>
                        <input type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_init_payment_timeout') border-red-500 @enderror"
                               id="freemopay_init_payment_timeout" name="freemopay_init_payment_timeout"
                               value="{{ old('freemopay_init_payment_timeout', $config->freemopay_init_payment_timeout ?? 5) }}"
                               min="1" max="30" required>
                        @error('freemopay_init_payment_timeout')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="freemopay_status_check_timeout" class="block mb-2 text-sm font-medium text-gray-900">
                            Timeout vérif statut (s)
                        </label>
                        <input type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_status_check_timeout') border-red-500 @enderror"
                               id="freemopay_status_check_timeout" name="freemopay_status_check_timeout"
                               value="{{ old('freemopay_status_check_timeout', $config->freemopay_status_check_timeout ?? 5) }}"
                               min="1" max="30" required>
                        @error('freemopay_status_check_timeout')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="freemopay_token_timeout" class="block mb-2 text-sm font-medium text-gray-900">
                            Timeout token (s)
                        </label>
                        <input type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_token_timeout') border-red-500 @enderror"
                               id="freemopay_token_timeout" name="freemopay_token_timeout"
                               value="{{ old('freemopay_token_timeout', $config->freemopay_token_timeout ?? 10) }}"
                               min="1" max="30" required>
                        @error('freemopay_token_timeout')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="freemopay_token_cache_duration" class="block mb-2 text-sm font-medium text-gray-900">
                            Durée cache token (s)
                        </label>
                        <input type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_token_cache_duration') border-red-500 @enderror"
                               id="freemopay_token_cache_duration" name="freemopay_token_cache_duration"
                               value="{{ old('freemopay_token_cache_duration', $config->freemopay_token_cache_duration ?? 3000) }}"
                               min="60" max="3600" required>
                        @error('freemopay_token_cache_duration')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-gray-500">3000s = 50 min (token expire à 60 min)</p>
                    </div>

                    <div>
                        <label for="freemopay_max_retries" class="block mb-2 text-sm font-medium text-gray-900">
                            Nombre de tentatives
                        </label>
                        <input type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_max_retries') border-red-500 @enderror"
                               id="freemopay_max_retries" name="freemopay_max_retries"
                               value="{{ old('freemopay_max_retries', $config->freemopay_max_retries ?? 2) }}"
                               min="0" max="5" required>
                        @error('freemopay_max_retries')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="freemopay_retry_delay" class="block mb-2 text-sm font-medium text-gray-900">
                            Délai entre tentatives (s)
                        </label>
                        <input type="number" step="0.1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 @error('freemopay_retry_delay') border-red-500 @enderror"
                               id="freemopay_retry_delay" name="freemopay_retry_delay"
                               value="{{ old('freemopay_retry_delay', $config->freemopay_retry_delay ?? 0.5) }}"
                               min="0" max="5" required>
                        @error('freemopay_retry_delay')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        <i class="fas fa-save mr-2"></i> Sauvegarder
                    </button>
                    <button type="button" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-300" onclick="testService('FreeMoPay', '{{ route('admin.service-config.test-freemopay') }}')">
                        <i class="fas fa-check-circle mr-2"></i> Tester la connexion
                    </button>
                </div>
            </form>
        </div>

        <div>
            <div class="bg-gray-50 border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6">
                    <h5 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i> Aide FreeMoPay
                    </h5>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Configuration requise:</p>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li>Compte FreeMoPay Business</li>
                                <li>App Key et Secret Key</li>
                                <li>URL de callback publique (HTTPS)</li>
                            </ul>
                        </div>
                        <hr class="border-gray-200">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Où trouver vos credentials:</p>
                            <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1">
                                <li>Connectez-vous à votre <a href="https://business.freemopay.com" target="_blank" class="text-blue-600 hover:underline">compte FreeMoPay Business</a></li>
                                <li>Accédez à "Paramètres API"</li>
                                <li>Copiez votre App Key et Secret Key</li>
                            </ol>
                        </div>
                        <hr class="border-gray-200">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 mb-2">Callback URL:</p>
                            <p class="text-sm text-gray-700 mb-2">FreeMoPay enverra les notifications de paiement à cette URL. Elle doit être:</p>
                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                <li>Accessible depuis Internet</li>
                                <li>En HTTPS (production)</li>
                                <li>Capable de traiter les POST requests</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
