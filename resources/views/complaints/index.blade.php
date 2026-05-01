@extends('layouts.app')

@section('title', 'Complaints - HotelMaint Pro')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Guest Complaints</h1>
        <p class="text-gray-600">Track and manage all guest maintenance complaints</p>
    </div>
    <a href="{{ route('complaints.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md transition-colors flex items-center">
        <span class="mr-2">➕</span> Log Complaint
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="{{ route('complaints.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Category</label>
            <select name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Categories</option>
                <option value="plumbing" {{ request('category') == 'plumbing' ? 'selected' : '' }}>Plumbing</option>
                <option value="electrical" {{ request('category') == 'electrical' ? 'selected' : '' }}>Electrical</option>
                <option value="hvac" {{ request('category') == 'hvac' ? 'selected' : '' }}>HVAC</option>
                <option value="furniture" {{ request('category') == 'furniture' ? 'selected' : '' }}>Furniture</option>
                <option value="lighting" {{ request('category') == 'lighting' ? 'selected' : '' }}>Lighting</option>
                <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Room Number</label>
            <input type="text" name="room" value="{{ request('room') }}" placeholder="e.g., 101" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">Filter</button>
        </div>
    </form>
</div>

<!-- Complaints List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($complaints as $complaint)
                <tr class="hover:bg-gray-50 priority-{{ $complaint->priority ?? 'medium' }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ $complaint->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Room {{ $complaint->room_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $complaint->guest_name ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ ucfirst($complaint->category) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ Str::limit($complaint->description, 50) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="status-badge status-{{ $complaint->status }}">{{ ucfirst(str_replace('_', ' ', $complaint->status)) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $complaint->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('complaints.show', $complaint) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        @if(in_array(auth()->user()->role->name, ['admin', 'front_desk', 'supervisor']))
                            <a href="{{ route('complaints.edit', $complaint) }}" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <span class="text-4xl block mb-2">📭</span>
                        No complaints found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="mt-6">
    {{ $complaints->links() }}
</div>
@endsection
