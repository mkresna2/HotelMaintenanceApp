@extends('layouts.app')

@section('title', 'Assets - HotelMaint Pro')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Assets</h1>
        <p class="text-gray-600">Manage hotel facilities and equipment</p>
    </div>
    <a href="{{ route('assets.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors flex items-center">
        <span class="mr-2">➕</span> Add Asset
    </a>
</div>

<!-- Asset Health Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
        <p class="text-sm text-gray-500">Healthy Assets</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['healthy'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
        <p class="text-sm text-gray-500">Needs Attention</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['warning'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
        <p class="text-sm text-gray-500">Critical</p>
        <p class="text-2xl font-bold text-red-600">{{ $stats['critical'] ?? 0 }}</p>
    </div>
</div>

<!-- Assets List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Health Score</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($assets as $asset)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $asset->asset_id }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $asset->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $asset->category->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $asset->location ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($asset->health_score >= 80)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $asset->health_score }}%</span>
                        @elseif($asset->health_score >= 50)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $asset->health_score }}%</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $asset->health_score }}%</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="status-badge status-{{ $asset->status }}">{{ ucfirst($asset->status) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('assets.show', $asset) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        <a href="{{ route('assets.edit', $asset) }}" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <span class="text-4xl block mb-2">🏢</span>
                        No assets found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $assets->links() }}
</div>
@endsection
