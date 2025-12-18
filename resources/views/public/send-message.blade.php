<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Envoyer un message à {{ $user->first_name ?? $user->username }} - Weylo</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf4ff',
                            100: '#fae8ff',
                            200: '#f5d0fe',
                            300: '#f0abfc',
                            400: '#e879f9',
                            500: '#d946ef',
                            600: '#c026d3',
                            700: '#a21caf',
                            800: '#86198f',
                            900: '#701a75',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-fuchsia-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-comment-dots text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                            Weylo
                        </h1>
                        <p class="text-xs text-gray-500">Messages anonymes</p>
                    </div>
                </div>
                <a href="/" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-home mr-1"></i>
                    Accueil
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- User Profile Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-6 fade-in-up">
            <div class="bg-gradient-to-r from-primary-500 to-purple-600 h-24"></div>
            <div class="px-6 pb-6">
                <div class="flex flex-col items-center -mt-12">
                    <!-- Avatar -->
                    @if($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->first_name }}"
                             class="w-24 h-24 rounded-full border-4 border-white shadow-lg object-cover">
                    @else
                        <div class="w-24 h-24 rounded-full border-4 border-white shadow-lg bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center">
                            <span class="text-4xl font-bold text-white">
                                {{ strtoupper(substr($user->first_name ?? $user->username, 0, 1)) }}
                            </span>
                        </div>
                    @endif

                    <!-- User Info -->
                    <h2 class="mt-4 text-2xl font-bold text-gray-900">
                        {{ $user->first_name }} {{ $user->last_name }}
                    </h2>
                    <p class="text-gray-500">{{ '@' . $user->username }}</p>

                    @if($user->bio)
                        <p class="mt-3 text-center text-gray-600 max-w-md">
                            {{ $user->bio }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Message Form Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden fade-in-up" style="animation-delay: 0.2s;">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-primary-50 to-purple-50">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-envelope text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900">
                            Envoyer un message anonyme
                        </h3>
                        <p class="text-sm text-gray-600">
                            Votre identité restera secrète
                        </p>
                    </div>
                </div>
            </div>

            <form id="messageForm" class="p-6">
                @csrf
                <input type="hidden" name="recipient_username" value="{{ $user->username }}">

                <!-- Message Textarea -->
                <div class="mb-6">
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment-dots text-primary-500 mr-1"></i>
                        Votre message
                    </label>
                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        maxlength="1000"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                        placeholder="Écrivez votre message anonyme ici..."
                        required
                    ></textarea>
                    <div class="flex items-center justify-between mt-2">
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-lock mr-1"></i>
                            Votre message est 100% anonyme
                        </p>
                        <span id="charCount" class="text-xs text-gray-500">0/1000</span>
                    </div>
                </div>

                <!-- Registration Section (Hidden initially) -->
                <div id="registrationSection" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                    <div class="flex items-start mb-4">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user-plus text-white"></i>
                        </div>
                        <div class="ml-3">
                            <h4 class="font-semibold text-gray-900">Créez votre compte en 2 secondes</h4>
                            <p class="text-sm text-gray-600">Pour envoyer ce message, créez rapidement votre compte</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Prénom
                            </label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                                placeholder="Votre prénom"
                            >
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Numéro de téléphone
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                                placeholder="+237 6XX XX XX XX"
                            >
                        </div>

                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Votre compte sera créé automatiquement. Vous recevrez vos identifiants par SMS.
                        </p>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-2"></i>
                        <p id="errorText" class="text-sm text-red-700"></p>
                    </div>
                </div>

                <!-- Success Message -->
                <div id="successMessage" class="hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                        <p class="text-sm text-green-700">Message envoyé avec succès ! 🎉</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-gradient-to-r from-primary-500 to-purple-600 text-white font-semibold py-4 px-6 rounded-xl hover:from-primary-600 hover:to-purple-700 transition-all transform hover:scale-105 flex items-center justify-center shadow-lg"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    <span id="btnText">Envoyer le message</span>
                </button>
            </form>
        </div>

        <!-- Info Card -->
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in-up" style="animation-delay: 0.4s;">
            <h4 class="font-semibold text-gray-900 mb-3">
                <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                Pourquoi Weylo ?
            </h4>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span>Messages 100% anonymes et sécurisés</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span>Inscription rapide en 2 secondes</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span>Chat en temps réel avec streaks</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span>Cadeaux virtuels et confessions publiques</span>
                </li>
            </ul>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-12 pb-8">
        <div class="max-w-2xl mx-auto px-4 text-center">
            <p class="text-sm text-gray-500">
                &copy; {{ date('Y') }} Weylo. Tous droits réservés.
            </p>
            <div class="mt-2 flex items-center justify-center space-x-4 text-xs text-gray-400">
                <a href="#" class="hover:text-primary-600">Conditions</a>
                <span>•</span>
                <a href="#" class="hover:text-primary-600">Confidentialité</a>
                <span>•</span>
                <a href="#" class="hover:text-primary-600">Support</a>
            </div>
        </div>
    </footer>

    <script>
        // Character counter
        const messageTextarea = document.getElementById('message');
        const charCount = document.getElementById('charCount');

        messageTextarea.addEventListener('input', function() {
            charCount.textContent = `${this.value.length}/1000`;
        });

        // Form submission
        const form = document.getElementById('messageForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const registrationSection = document.getElementById('registrationSection');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const successMessage = document.getElementById('successMessage');

        let registrationRequired = false;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const message = messageTextarea.value.trim();

            if (!message) {
                showError('Veuillez entrer un message.');
                return;
            }

            // Hide previous messages
            errorMessage.classList.add('hidden');
            successMessage.classList.add('hidden');

            // Show loading state
            submitBtn.disabled = true;
            btnText.textContent = 'Envoi en cours...';
            submitBtn.innerHTML = `
                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Envoi en cours...
            `;

            try {
                // First attempt: Try to send the message
                if (!registrationRequired) {
                    const formData = new FormData(form);

                    const response = await fetch('/send-anonymous-message', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.status === 401) {
                        // User not authenticated, show registration form
                        registrationRequired = true;
                        registrationSection.classList.remove('hidden');
                        registrationSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        // Update button text
                        resetButton();
                        btnText.textContent = 'Créer mon compte et envoyer';

                        // Make fields required
                        document.getElementById('first_name').required = true;
                        document.getElementById('phone').required = true;

                        showError('Vous devez créer un compte pour envoyer un message anonyme.');
                        return;
                    }

                    if (response.ok) {
                        // Success!
                        showSuccess();
                        form.reset();
                        charCount.textContent = '0/1000';
                    } else {
                        showError(data.message || 'Une erreur est survenue.');
                    }
                } else {
                    // Registration + Send message
                    const firstName = document.getElementById('first_name').value.trim();
                    const phone = document.getElementById('phone').value.trim();

                    if (!firstName || !phone) {
                        showError('Veuillez remplir tous les champs.');
                        resetButton();
                        return;
                    }

                    const formData = new FormData(form);

                    const response = await fetch('/register-and-send', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok) {
                        showSuccess();
                        form.reset();
                        charCount.textContent = '0/1000';
                        registrationSection.classList.add('hidden');

                        // Show additional success message about account
                        setTimeout(() => {
                            alert('🎉 Votre compte a été créé ! Vous recevrez vos identifiants par SMS.');
                        }, 1000);
                    } else {
                        showError(data.message || 'Une erreur est survenue.');
                    }
                }

                resetButton();
            } catch (error) {
                console.error('Error:', error);
                showError('Erreur de connexion. Veuillez réessayer.');
                resetButton();
            }
        });

        function showError(message) {
            errorText.textContent = message;
            errorMessage.classList.remove('hidden');
            errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function showSuccess() {
            successMessage.classList.remove('hidden');
            successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Hide after 5 seconds
            setTimeout(() => {
                successMessage.classList.add('hidden');
            }, 5000);
        }

        function resetButton() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <i class="fas fa-paper-plane mr-2"></i>
                <span id="btnText">${registrationRequired ? 'Créer mon compte et envoyer' : 'Envoyer le message'}</span>
            `;
        }
    </script>
</body>
</html>
