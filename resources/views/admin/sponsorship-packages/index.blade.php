@extends('admin.layouts.app')

@section('title', 'Sponsoring')
@section('header', 'Packages Sponsoring')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-2xl font-bold text-gray-800">Packages Sponsoring</h2>
    <a href="{{ route('admin.sponsorship-packages.create') }}"
       class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 inline-flex items-center">
        <i class="fas fa-plus mr-2"></i>Nouveau package
    </a>
</div>

@if(session('success'))
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audience</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($packages as $package)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            #{{ $package->id }}
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $package->name }}</div>
                                @if($package->description)
                                    <div class="text-sm text-gray-500">{{ Str::limit($package->description, 60) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $package->reach_label }} users
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $package->duration_days }} jours
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-900">{{ $package->formatted_price }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($package->is_active)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                    Actif
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('admin.sponsorship-packages.edit', $package) }}"
                               class="text-primary-600 hover:text-primary-900 mr-3">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <form action="{{ route('admin.sponsorship-packages.destroy', $package) }}"
                                  method="POST"
                                  class="inline-block"
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce package ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-bullhorn text-4xl mb-4"></i>
                            <p>Aucun package sponsoring trouvé</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($packages->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $packages->links() }}
        </div>
    @endif
</div>
@endsection
