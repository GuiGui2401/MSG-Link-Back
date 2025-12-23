@extends('admin.layouts.app')

@section('title', 'Pages Légales')
@section('header', 'Gestion des Pages Légales')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <p class="text-gray-600">Gérez les pages légales affichées dans le footer du site</p>
    </div>
    <a href="{{ route('admin.legal-pages.create') }}" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors inline-flex items-center">
        <i class="fas fa-plus mr-2"></i>Nouvelle Page
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <span>{{ session('success') }}</span>
        </div>
    </div>
@endif

<!-- Legal Pages Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ordre</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contenu</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dernière MAJ</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($pages as $page)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $page->order }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($page->is_active)
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                                @else
                                    <span class="w-2 h-2 bg-gray-300 rounded-full mr-3"></span>
                                @endif
                                <span class="text-sm font-medium text-gray-900">{{ $page->title }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded">{{ $page->slug }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($page->is_active)
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> Actif
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <i class="fas fa-times-circle mr-1"></i> Inactif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($page->content)
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <i class="fas fa-file-alt mr-1"></i> Présent
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Vide
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $page->updated_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.legal-pages.edit', $page) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.legal-pages.destroy', $page) }}" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette page ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-file-alt text-4xl mb-4"></i>
                                <p class="text-lg font-medium">Aucune page légale</p>
                                <p class="text-sm">Créez votre première page légale pour commencer</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Info Box -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex items-start">
        <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
        <div class="text-sm text-blue-800">
            <p class="font-semibold mb-2">Comment ça fonctionne ?</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Les pages <strong>actives</strong> avec du <strong>contenu</strong> s'affichent automatiquement dans le footer du site</li>
                <li>L'<strong>ordre</strong> détermine la position d'affichage dans le footer</li>
                <li>Le <strong>slug</strong> est utilisé dans l'URL de la page (ex: /legal/cgu)</li>
                <li>Utilisez l'éditeur HTML pour formater votre contenu</li>
            </ul>
        </div>
    </div>
</div>
@endsection
