@extends('admin.layouts.app')

@section('title', 'Modifier package sponsoring')
@section('header', 'Modifier le package sponsoring')

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.sponsorship-packages.update', $package) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $package->name) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('name') border-red-500 @enderror"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description (optionnel)
                    </label>
                    <textarea name="description"
                              id="description"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('description') border-red-500 @enderror">{{ old('description', $package->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reach_min" class="block text-sm font-medium text-gray-700 mb-2">
                        Audience min (users) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="reach_min"
                           id="reach_min"
                           value="{{ old('reach_min', $package->reach_min) }}"
                           min="1"
                           step="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('reach_min') border-red-500 @enderror"
                           required>
                    @error('reach_min')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reach_max" class="block text-sm font-medium text-gray-700 mb-2">
                        Audience max (users)
                    </label>
                    <input type="number"
                           name="reach_max"
                           id="reach_max"
                           value="{{ old('reach_max', $package->reach_max) }}"
                           min="1"
                           step="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('reach_max') border-red-500 @enderror">
                    @error('reach_max')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                        Prix (FCFA) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="price"
                           id="price"
                           value="{{ old('price', $package->price) }}"
                           min="0"
                           step="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('price') border-red-500 @enderror"
                           required>
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="duration_days" class="block text-sm font-medium text-gray-700 mb-2">
                        Période (jours) <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="duration_days"
                           id="duration_days"
                           value="{{ old('duration_days', $package->duration_days) }}"
                           min="1"
                           step="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500 @error('duration_days') border-red-500 @enderror"
                           required>
                    @error('duration_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $package->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Package actif</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-4">
                <a href="{{ route('admin.sponsorship-packages.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    <i class="fas fa-save mr-2"></i>Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
