@extends('admin.layouts.app')

@section('title', 'Configuration des Services')

@section('content')
<div class="w-full px-4 md:px-6">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <h4 class="text-2xl font-semibold text-gray-900">Configuration des Services API</h4>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 inline-flex items-center">
                            Tableau de bord
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="text-gray-500 ml-1 md:ml-2">Configuration Services</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg flex items-center justify-between" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="text-green-700 hover:text-green-900" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg flex items-center justify-between" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" class="text-red-700 hover:text-red-900" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded-lg flex items-center justify-between" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>{{ session('warning') }}</span>
            </div>
            <button type="button" class="text-yellow-700 hover:text-yellow-900" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h4 class="text-xl font-semibold text-gray-900">Gestion des Credentials API</h4>
                <div class="flex items-center space-x-2">
                    <button type="button" class="px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-600 rounded-lg hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt mr-1"></i> Recharger
                    </button>
                    <form action="{{ route('admin.service-config.clear-cache') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            <i class="fas fa-redo mr-1"></i> Vider le cache
                        </button>
                    </form>
                </div>
            </div>

            <div class="border-b border-gray-200 mb-6">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="serviceConfigTabs" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button class="inline-flex items-center px-4 py-3 border-b-2 border-blue-600 text-blue-600 rounded-t-lg active" id="whatsapp-tab" data-tabs-target="#whatsapp" type="button" role="tab" aria-controls="whatsapp" aria-selected="true">
                            <i class="fab fa-whatsapp mr-2"></i> WhatsApp
                            @if($whatsappConfig && $whatsappConfig->isConfigured())
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Configuré</span>
                            @else
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Non configuré</span>
                            @endif
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-flex items-center px-4 py-3 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 rounded-t-lg" id="sms-tab" data-tabs-target="#sms" type="button" role="tab" aria-controls="sms" aria-selected="false">
                            <i class="fas fa-sms mr-2"></i> SMS (Nexah)
                            @if($nexahConfig && $nexahConfig->isConfigured())
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Configuré</span>
                            @else
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Non configuré</span>
                            @endif
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-flex items-center px-4 py-3 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 rounded-t-lg" id="payment-tab" data-tabs-target="#payment" type="button" role="tab" aria-controls="payment" aria-selected="false">
                            <i class="fas fa-dollar-sign mr-2"></i> Paiement (FreeMoPay)
                            @if($freemopayConfig && $freemopayConfig->isConfigured())
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-800 rounded-full">Configuré</span>
                            @else
                                <span class="ml-2 px-2 py-0.5 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">Non configuré</span>
                            @endif
                        </button>
                    </li>
                    <li role="presentation">
                        <button class="inline-flex items-center px-4 py-3 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300 rounded-t-lg" id="preferences-tab" data-tabs-target="#preferences" type="button" role="tab" aria-controls="preferences" aria-selected="false">
                            <i class="fas fa-cog mr-2"></i> Préférences
                        </button>
                    </li>
                </ul>
            </div>

            <div id="serviceConfigTabsContent">
                {{-- WhatsApp Tab --}}
                @include('admin.service-config.whatsapp', ['config' => $whatsappConfig])

                {{-- SMS Tab --}}
                @include('admin.service-config.nexah', ['config' => $nexahConfig])

                {{-- Payment Tab --}}
                @include('admin.service-config.freemopay', ['config' => $freemopayConfig])

                {{-- Preferences Tab --}}
                @include('admin.service-config.preferences')
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('[role="tab"]');
        const tabPanels = document.querySelectorAll('[role="tabpanel"]');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetId = this.getAttribute('data-tabs-target');

                // Remove active classes from all tabs
                tabs.forEach(t => {
                    t.classList.remove('border-blue-600', 'text-blue-600', 'active');
                    t.classList.add('border-transparent', 'text-gray-500');
                });

                // Hide all tab panels
                tabPanels.forEach(panel => {
                    panel.classList.add('hidden');
                    panel.classList.remove('block');
                });

                // Add active classes to clicked tab
                this.classList.remove('border-transparent', 'text-gray-500');
                this.classList.add('border-blue-600', 'text-blue-600', 'active');

                // Show target panel
                const targetPanel = document.querySelector(targetId);
                if (targetPanel) {
                    targetPanel.classList.remove('hidden');
                    targetPanel.classList.add('block');
                }
            });
        });
    });

    // Test connection functions
    function testService(serviceName, url) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test en cours...';

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            alert('❌ Erreur lors du test: ' + error.message);
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    // Toggle password visibility
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = event.target;

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // WhatsApp Test Modal Functions
    function openWhatsAppTestModal() {
        document.getElementById('whatsappTestModal').style.display = 'flex';
    }

    function closeWhatsAppTestModal() {
        document.getElementById('whatsappTestModal').style.display = 'none';
        document.getElementById('whatsapp_test_result').style.display = 'none';
    }

    function sendWhatsAppTest() {
        const phone = document.getElementById('test_phone_whatsapp').value;
        const otp = document.getElementById('test_otp_whatsapp').value || Math.floor(100000 + Math.random() * 900000);
        const resultDiv = document.getElementById('whatsapp_test_result');

        if (!phone) {
            resultDiv.className = 'bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg';
            resultDiv.innerHTML = 'Veuillez entrer un numéro de téléphone';
            resultDiv.style.display = 'block';
            return;
        }

        resultDiv.className = 'bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded-lg';
        resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Envoi en cours...';
        resultDiv.style.display = 'block';

        fetch('{{ route("admin.service-config.send-test-whatsapp") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                phone: phone,
                otp: otp
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.className = 'bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg';
                resultDiv.innerHTML = '✅ ' + data.message + '<br><small>OTP envoyé: ' + otp + '</small>';
            } else {
                resultDiv.className = 'bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg';
                resultDiv.innerHTML = '❌ ' + data.message;
            }
            resultDiv.style.display = 'block';
        })
        .catch(error => {
            resultDiv.className = 'bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg';
            resultDiv.innerHTML = '❌ Erreur: ' + error.message;
            resultDiv.style.display = 'block';
        });
    }

    // Nexah Test Modal Functions
    function openNexahTestModal() {
        document.getElementById('nexahTestModal').style.display = 'flex';
    }

    function closeNexahTestModal() {
        document.getElementById('nexahTestModal').style.display = 'none';
        document.getElementById('nexah_test_result').style.display = 'none';
    }

    function sendNexahTest() {
        const phone = document.getElementById('test_phone_nexah').value;
        const message = document.getElementById('test_message_nexah').value;
        const resultDiv = document.getElementById('nexah_test_result');

        if (!phone || !message) {
            resultDiv.className = 'bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg';
            resultDiv.innerHTML = 'Veuillez remplir tous les champs';
            resultDiv.style.display = 'block';
            return;
        }

        resultDiv.className = 'bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded-lg';
        resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Envoi en cours...';
        resultDiv.style.display = 'block';

        fetch('{{ route("admin.service-config.send-test-nexah") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                phone: phone,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.className = 'bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-lg';
                resultDiv.innerHTML = '✅ ' + data.message;
            } else {
                resultDiv.className = 'bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg';
                resultDiv.innerHTML = '❌ ' + data.message;
            }
            resultDiv.style.display = 'block';
        })
        .catch(error => {
            resultDiv.className = 'bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg';
            resultDiv.innerHTML = '❌ Erreur: ' + error.message;
            resultDiv.style.display = 'block';
        });
    }
</script>
@endpush
@endsection
