@extends('admin.layouts.app')

@section('title', 'Apercu des flammes')
@section('header', 'Apercu des flammes')

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Preview des paliers de flammes</h2>
            <p class="text-sm text-gray-600 mt-1">Basé sur les valeurs configurées dans les paramètres.</p>
        </div>
        <a href="{{ route('admin.settings') }}"
           class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour aux paramètres
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-md font-semibold text-gray-800 mb-4">
            <i class="fas fa-sliders-h text-purple-600 mr-2"></i>
            Seuils actuels
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-lg border border-gray-200">
                <p class="text-xs text-gray-500">Flamme jaune</p>
                <p class="text-lg font-semibold text-gray-900">{{ $thresholds['yellow'] }} jours</p>
            </div>
            <div class="p-4 rounded-lg border border-gray-200">
                <p class="text-xs text-gray-500">Flamme orange</p>
                <p class="text-lg font-semibold text-gray-900">{{ $thresholds['orange'] }} jours</p>
            </div>
            <div class="p-4 rounded-lg border border-gray-200">
                <p class="text-xs text-gray-500">Flamme violette</p>
                <p class="text-lg font-semibold text-gray-900">{{ $thresholds['purple'] }} jours</p>
            </div>
        </div>
        @if(!$isOrdered)
        <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
            <p class="text-sm text-yellow-700">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Attention: les seuils ne sont pas croissants. Verifiez l'ordre (jaune ≤ orange ≤ violet).
            </p>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-md font-semibold text-gray-800 mb-4">
            <i class="fas fa-fire text-orange-500 mr-2"></i>
            Apercu visuel
        </h3>
        <div class="space-y-4">
            @foreach($previewRows as $row)
            @php
                $bgColor = $row['color'] ?? '#E5E7EB';
                $textColor = $row['color'] ? '#111827' : '#6B7280';
            @endphp
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full"
                          style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                        @if($row['level'] === 'purple')
                            <span class="text-base">💜🔥</span>
                        @elseif($row['level'] === 'none')
                            <span class="text-base">—</span>
                        @else
                            <span class="text-lg">🔥</span>
                        @endif
                    </span>
                    <div class="ml-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $row['label'] }}</p>
                        <p class="text-xs text-gray-500">Exemple: {{ $row['count'] }} jours</p>
                    </div>
                </div>
                <div class="text-lg font-bold text-gray-900">{{ $row['count'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
