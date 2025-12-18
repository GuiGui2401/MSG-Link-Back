@extends('admin.layouts.app')

@section('title', 'Générateur de liens')
@section('header', 'Générateur de liens')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Description -->
    <div class="bg-gradient-to-br from-primary-500 to-purple-600 rounded-xl shadow-lg p-6 mb-8 text-white">
        <div class="flex items-start">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-magic text-2xl"></i>
            </div>
            <div class="ml-4">
                <h2 class="text-xl font-bold mb-2">Générateur de liens anonymes</h2>
                <p class="text-white/90 text-sm">
                    Créez des liens personnalisés pour permettre aux utilisateurs de vous envoyer des messages anonymes.
                    Partagez votre lien sur les réseaux sociaux, par email ou SMS !
                </p>
            </div>
        </div>
    </div>

    <!-- My Profile Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-user-circle text-primary-500 mr-2"></i>
                Mon profil
            </h3>
        </div>

        <div class="p-6">
            <div class="bg-gradient-to-r from-primary-50 to-purple-50 border-2 border-primary-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->first_name }}" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr(auth()->user()->first_name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div class="ml-3">
                        <p class="font-semibold text-gray-900">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                        <p class="text-sm text-gray-600">{{ '@' . auth()->user()->username }}</p>
                    </div>
                </div>
            </div>

            <!-- Optional Welcome Message -->
            <div class="mb-6">
                <label for="welcomeMessage" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-comment-dots text-primary-500 mr-1"></i>
                    Message d'accueil personnalisé (optionnel)
                </label>
                <textarea
                    id="welcomeMessage"
                    name="welcome_message"
                    rows="3"
                    maxlength="200"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                    placeholder="Ex: Envoie-moi tes secrets, tes questions, ou juste un message..."
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">Ce message s'affichera sur votre page de réception</p>
            </div>

            <!-- Generate Button -->
            <button
                type="button"
                id="generateBtn"
                class="w-full bg-gradient-to-r from-primary-500 to-purple-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-primary-600 hover:to-purple-700 transition-all transform hover:scale-105 flex items-center justify-center"
            >
                <i class="fas fa-sparkles mr-2"></i>
                Générer mon lien
            </button>
        </div>
    </div>

    <!-- Generated Link Display -->
    <div id="generatedLinkCard" class="hidden bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
            <h3 class="text-lg font-semibold text-green-800">
                <i class="fas fa-check-circle mr-2"></i>
                Lien généré avec succès !
            </h3>
        </div>

        <div class="p-6 space-y-6">
            <!-- Full Link -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Lien complet
                </label>
                <div class="flex items-center space-x-2">
                    <input
                        type="text"
                        id="fullLink"
                        readonly
                        class="flex-1 px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 font-mono text-sm"
                        value=""
                    >
                    <button
                        type="button"
                        onclick="copyToClipboard('fullLink', this)"
                        class="px-4 py-3 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition flex items-center"
                    >
                        <i class="fas fa-copy mr-2"></i>
                        Copier
                    </button>
                </div>
            </div>

            <!-- Short Link -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Lien court (partage)
                </label>
                <div class="flex items-center space-x-2">
                    <input
                        type="text"
                        id="shortLink"
                        readonly
                        class="flex-1 px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 font-mono text-sm"
                        value=""
                    >
                    <button
                        type="button"
                        onclick="copyToClipboard('shortLink', this)"
                        class="px-4 py-3 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition flex items-center"
                    >
                        <i class="fas fa-copy mr-2"></i>
                        Copier
                    </button>
                </div>
            </div>

            <!-- Share Options -->
            <div>
                <p class="text-sm font-medium text-gray-700 mb-3">Partager directement</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onclick="shareOnWhatsApp()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center text-sm"
                    >
                        <i class="fab fa-whatsapp mr-2"></i>
                        WhatsApp
                    </button>
                    <button
                        type="button"
                        onclick="shareOnTwitter()"
                        class="px-4 py-2 bg-blue-400 text-white rounded-lg hover:bg-blue-500 transition flex items-center text-sm"
                    >
                        <i class="fab fa-twitter mr-2"></i>
                        Twitter
                    </button>
                    <button
                        type="button"
                        onclick="shareOnFacebook()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center text-sm"
                    >
                        <i class="fab fa-facebook mr-2"></i>
                        Facebook
                    </button>
                    <button
                        type="button"
                        onclick="shareOnTelegram()"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition flex items-center text-sm"
                    >
                        <i class="fab fa-telegram mr-2"></i>
                        Telegram
                    </button>
                </div>
            </div>

            <!-- QR Code -->
            <div class="pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium text-gray-700">Code QR</p>
                    <button
                        type="button"
                        onclick="downloadQR()"
                        class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                    >
                        <i class="fas fa-download mr-1"></i>
                        Télécharger
                    </button>
                </div>
                <div id="qrCode" class="flex justify-center bg-white p-4 rounded-lg border border-gray-200">
                    <!-- QR Code will be generated here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
const currentUser = {
    username: '{{ auth()->user()->username }}',
    first_name: '{{ auth()->user()->first_name }}',
    last_name: '{{ auth()->user()->last_name }}'
};

let qrCodeInstance = null;

// Generate link
document.getElementById('generateBtn').addEventListener('click', function() {
    const userId = '{{ auth()->user()->id }}';
    const baseUrl = window.location.origin;
    const fullLink = `${baseUrl}/m/${userId}`;
    const shortLink = `/m/${userId}`;

    // Display links
    document.getElementById('fullLink').value = fullLink;
    document.getElementById('shortLink').value = shortLink;

    // Show generated link card
    document.getElementById('generatedLinkCard').classList.remove('hidden');

    // Generate QR Code
    generateQRCode(fullLink);

    // Scroll to result
    document.getElementById('generatedLinkCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
});

// Generate QR Code
function generateQRCode(url) {
    const qrDiv = document.getElementById('qrCode');
    qrDiv.innerHTML = ''; // Clear previous QR code

    qrCodeInstance = new QRCode(qrDiv, {
        text: url,
        width: 200,
        height: 200,
        colorDark: "#d946ef",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}

// Copy to clipboard
function copyToClipboard(inputId, btn) {
    const input = document.getElementById(inputId);
    input.select();
    document.execCommand('copy');

    // Visual feedback
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check mr-2"></i>Copié !';
    btn.classList.add('bg-green-500');
    btn.classList.remove('bg-primary-500', 'bg-purple-500');

    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.remove('bg-green-500');
        if (inputId === 'fullLink') {
            btn.classList.add('bg-primary-500');
        } else {
            btn.classList.add('bg-purple-500');
        }
    }, 2000);
}

// Share functions
function shareOnWhatsApp() {
    const link = document.getElementById('fullLink').value;
    const text = `Envoie-moi un message anonyme sur Weylo ! ${link}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
}

function shareOnTwitter() {
    const link = document.getElementById('fullLink').value;
    const text = `Envoie-moi un message anonyme sur Weylo !`;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(link)}`, '_blank');
}

function shareOnFacebook() {
    const link = document.getElementById('fullLink').value;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(link)}`, '_blank');
}

function shareOnTelegram() {
    const link = document.getElementById('fullLink').value;
    const text = `Envoie-moi un message anonyme sur Weylo !`;
    window.open(`https://t.me/share/url?url=${encodeURIComponent(link)}&text=${encodeURIComponent(text)}`, '_blank');
}

// Download QR Code
function downloadQR() {
    const canvas = document.querySelector('#qrCode canvas');
    if (!canvas) return;

    const link = document.createElement('a');
    link.download = `weylo-qr-${currentUser.username}.png`;
    link.href = canvas.toDataURL();
    link.click();
}
</script>
@endpush
