@extends('admin.layouts.app')

@section('title', 'Modifier Page Légale')
@section('header', 'Modifier: ' . $legalPage->title)

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('admin.legal-pages.update', $legalPage) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de base</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Titre -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Titre <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="title"
                           id="title"
                           value="{{ old('title', $legalPage->title) }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('title') border-red-500 @enderror">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                        Slug (URL) <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="slug"
                           id="slug"
                           value="{{ old('slug', $legalPage->slug) }}"
                           required
                           placeholder="ex: cgu, privacy, cookies"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('slug') border-red-500 @enderror">
                    @error('slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">URL de la page: /legal/<span id="slug-preview">{{ $legalPage->slug }}</span></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ordre -->
                <div>
                    <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                        Ordre d'affichage
                    </label>
                    <input type="number"
                           name="order"
                           id="order"
                           value="{{ old('order', $legalPage->order) }}"
                           min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 @error('order') border-red-500 @enderror">
                    @error('order')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Plus le nombre est petit, plus la page apparaît en premier</p>
                </div>

                <!-- Statut -->
                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">
                        Statut
                    </label>
                    <div class="flex items-center h-[42px]">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="is_active"
                                   id="is_active"
                                   value="1"
                                   {{ old('is_active', $legalPage->is_active) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Page active (visible sur le site)</span>
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">La page doit être active ET avoir du contenu pour s'afficher</p>
                </div>
            </div>
        </div>

        <!-- Contenu -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Contenu de la page</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Contenu de la page
                </label>
                <div id="quill-editor" class="bg-white"></div>
                <textarea name="content" id="content" class="hidden">{{ old('content', $legalPage->content) }}</textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Utilisez l'éditeur pour formater votre contenu (titres, gras, listes, liens, etc.)
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center">
            <a href="{{ route('admin.legal-pages.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </a>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                </button>
            </div>
        </div>
    </form>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<style>
    .ql-editor {
        min-height: 400px;
        font-size: 14px;
    }
    .ql-toolbar {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }
    .ql-container {
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    // Initialize Quill editor
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link'],
                ['clean']
            ]
        },
        placeholder: 'Écrivez le contenu de votre page ici...'
    });

    // Load existing content
    const existingContent = document.getElementById('content').value;
    if (existingContent) {
        quill.root.innerHTML = existingContent;
    }

    // Sync Quill content to hidden textarea before form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        document.getElementById('content').value = quill.root.innerHTML;
    });

    // Update slug preview
    document.getElementById('slug').addEventListener('input', function(e) {
        document.getElementById('slug-preview').textContent = e.target.value || 'slug';
    });
</script>
@endpush
@endsection
