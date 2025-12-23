@extends('admin.layouts.app')

@section('title', 'Mode Maintenance')
@section('header', 'Mode Maintenance')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gestion du Mode Maintenance</h2>
        <p class="mt-1 text-sm text-gray-600">
            Activez le mode maintenance pour bloquer l'accès à l'application pendant les mises à jour.
        </p>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Statut actuel</h3>
                <div class="flex items-center space-x-2">
                    @if($maintenanceMode->enabled ?? false)
                        <span class="flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Mode Maintenance ACTIF
                        </span>
                    @else
                        <span class="flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>
                            Application NORMALE
                        </span>
                    @endif
                </div>
            </div>

            <form action="{{ route('admin.maintenance.toggle') }}" method="POST">
                @csrf
                @if($maintenanceMode->enabled ?? false)
                    <button type="submit"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors shadow-sm flex items-center space-x-2">
                        <i class="fas fa-power-off"></i>
                        <span>Désactiver le Mode Maintenance</span>
                    </button>
                @else
                    <button type="submit"
                            onclick="return confirm('Êtes-vous sûr de vouloir activer le mode maintenance ? Les utilisateurs ne pourront plus accéder à l\'application.')"
                            class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors shadow-sm flex items-center space-x-2">
                        <i class="fas fa-power-off"></i>
                        <span>Activer le Mode Maintenance</span>
                    </button>
                @endif
            </form>
        </div>

        @if($maintenanceMode->enabled ?? false)
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-yellow-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-800">
                            <strong>Attention :</strong> L'application est actuellement en mode maintenance.
                            Seuls les administrateurs peuvent y accéder.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Configuration Form -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuration du Mode Maintenance</h3>

        <form action="{{ route('admin.maintenance.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Message de maintenance -->
            <div class="mb-6">
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                    Message affiché aux utilisateurs
                </label>
                <textarea
                    id="message"
                    name="message"
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all @error('message') border-red-500 @enderror"
                    placeholder="Entrez le message à afficher pendant la maintenance..."
                >{{ old('message', $maintenanceMode->message ?? 'Le site est actuellement en maintenance. Nous reviendrons bientôt !') }}</textarea>

                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <p class="mt-1 text-sm text-gray-500">
                    Ce message sera affiché aux utilisateurs pendant le mode maintenance.
                </p>
            </div>

            <!-- Heure de fin estimée -->
            <div class="mb-6">
                <label for="estimated_end_time" class="block text-sm font-medium text-gray-700 mb-2">
                    Fin estimée (optionnel)
                </label>
                <input
                    type="datetime-local"
                    id="estimated_end_time"
                    name="estimated_end_time"
                    value="{{ old('estimated_end_time', $maintenanceMode->estimated_end_time ? \Carbon\Carbon::parse($maintenanceMode->estimated_end_time)->format('Y-m-d\TH:i') : '') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all @error('estimated_end_time') border-red-500 @enderror"
                >

                @error('estimated_end_time')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <p class="mt-1 text-sm text-gray-500">
                    Indiquez quand la maintenance devrait se terminer (affiché aux utilisateurs).
                </p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end space-x-3">
                <button
                    type="submit"
                    class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors shadow-sm flex items-center space-x-2">
                    <i class="fas fa-save"></i>
                    <span>Enregistrer les paramètres</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Information Card -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600 text-xl"></i>
            </div>
            <div class="ml-3">
                <h4 class="text-sm font-semibold text-blue-900 mb-2">Comment ça marche ?</h4>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                    <li>Lorsque le mode maintenance est activé, tous les utilisateurs normaux ne peuvent plus accéder à l'application</li>
                    <li>Les administrateurs, super-administrateurs et modérateurs peuvent toujours accéder à l'application</li>
                    <li>Un message personnalisé est affiché aux utilisateurs pendant la maintenance</li>
                    <li>Vous pouvez spécifier une heure de fin estimée qui sera affichée aux utilisateurs</li>
                    <li>Le statut est vérifié automatiquement à chaque requête</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-dismiss flash messages after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('[role="alert"]');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
@endpush

@endsection
