@extends('layouts.app')

@section('title', 'Work Orders - HotelMaint Pro')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Work Orders</h1>
        <p class="text-gray-600">Manage all maintenance work orders</p>
    </div>
    <a href="{{ route('work-orders.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors flex items-center">
        <span class="mr-2">➕</span> Create Work Order
    </a>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-sm text-gray-500">Open</p>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['open'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-sm text-gray-500">In Progress</p>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['in_progress'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-sm text-gray-500">Pending Parts</p>
        <p class="text-2xl font-bold text-orange-600">{{ $stats['pending_parts'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-sm text-gray-500">Resolved</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['resolved'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <p class="text-sm text-gray-500">Closed</p>
        <p class="text-2xl font-bold text-gray-600">{{ $stats['closed'] ?? 0 }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form method="GET" action="{{ route('work-orders.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Statuses</option>
                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="pending_parts" {{ request('status') == 'pending_parts' ? 'selected' : '' }}>Pending Parts</option>
                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Priority</label>
            <select name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Priorities</option>
                <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Type</label>
            <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Types</option>
                <option value="corrective" {{ request('type') == 'corrective' ? 'selected' : '' }}>Corrective</option>
                <option value="preventive" {{ request('type') == 'preventive' ? 'selected' : '' }}>Preventive</option>
                <option value="emergency" {{ request('type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                <option value="inspection" {{ request('type') == 'inspection' ? 'selected' : '' }}>Inspection</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Assignee</label>
            <select name="assignee" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">All Technicians</option>
                @foreach($technicians as $tech)
                    <option value="{{ $tech->id }}" {{ request('assignee') == $tech->id ? 'selected' : '' }}>{{ $tech->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">Filter</button>
        </div>
    </form>
</div>

<!-- Work Orders List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WO #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($workOrders as $wo)
                <tr class="hover:bg-gray-50 priority-{{ $wo->priority }}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $wo->wo_number }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ $wo->title }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $wo->location ?? 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            {{ ucfirst($wo->type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($wo->priority === 'critical')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Critical</span>
                        @elseif($wo->priority === 'high')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">High</span>
                        @elseif($wo->priority === 'medium')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Medium</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Low</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $wo->assignedTo ? $wo->assignedTo->user->name : 'Unassigned' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="status-badge status-{{ $wo->status }}">{{ ucfirst(str_replace('_', ' ', $wo->status)) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($wo->due_date)
                            <span class="{{ $wo->due_date->isPast() ? 'text-red-600 font-bold' : '' }}">
                                {{ $wo->due_date->format('M d') }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('work-orders.show', $wo) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        @if(in_array(auth()->user()->role->name, ['admin', 'supervisor', 'technician']))
                            <a href="{{ route('work-orders.edit', $wo) }}" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                        <span class="text-4xl block mb-2">📋</span>
                        No work orders found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="mt-6">
    {{ $workOrders->links() }}
</div>
@endsection
