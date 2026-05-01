@extends('layouts.app')

@section('title', 'Maintenance Schedules - HotelMaint Pro')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Maintenance Schedules</h1>
        <p class="text-gray-600">Plan and track preventive maintenance</p>
    </div>
    <a href="{{ route('schedules.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors flex items-center">
        <span class="mr-2">➕</span> Create Schedule
    </a>
</div>

<!-- Calendar View Toggle -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <div class="flex space-x-4 mb-4">
        <button class="px-4 py-2 bg-blue-600 text-white rounded-md">Month View</button>
        <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Week View</button>
        <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Day View</button>
    </div>
    
    <!-- Simple Month Calendar Placeholder -->
    <div class="border rounded-lg overflow-hidden">
        <div class="grid grid-cols-7 bg-gray-50 border-b">
            <div class="p-2 text-center text-sm font-medium">Sun</div>
            <div class="p-2 text-center text-sm font-medium">Mon</div>
            <div class="p-2 text-center text-sm font-medium">Tue</div>
            <div class="p-2 text-center text-sm font-medium">Wed</div>
            <div class="p-2 text-center text-sm font-medium">Thu</div>
            <div class="p-2 text-center text-sm font-medium">Fri</div>
            <div class="p-2 text-center text-sm font-medium">Sat</div>
        </div>
        <div class="grid grid-cols-7">
            @for($i = 1; $i <= 31; $i++)
                <div class="min-h-24 p-2 border-t border-r {{ $i % 7 == 0 ? 'border-r-0' : '' }}">
                    <div class="text-sm font-medium text-gray-700">{{ $i }}</div>
                    @if($i % 5 == 0)
                        <div class="mt-1 text-xs bg-blue-100 text-blue-800 p-1 rounded truncate">HVAC Check</div>
                    @endif
                    @if($i % 7 == 0)
                        <div class="mt-1 text-xs bg-green-100 text-green-800 p-1 rounded truncate">Fire Safety</div>
                    @endif
                </div>
            @endfor
        </div>
    </div>
</div>

<!-- Upcoming Tasks -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold">Upcoming Scheduled Tasks</h2>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedule</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset/Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Frequency</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Due</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignee</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($schedules as $schedule)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium">{{ $schedule->name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $schedule->asset ? $schedule->asset->name : $schedule->location ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            {{ ucfirst($schedule->frequency) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm {{ $schedule->next_due && $schedule->next_due->isPast() ? 'text-red-600 font-bold' : '' }}">
                        {{ $schedule->next_due ? $schedule->next_due->format('M d, Y') : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $schedule->assignedTo ? $schedule->assignedTo->user->name : 'Unassigned' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="status-badge status-{{ $schedule->status }}">{{ ucfirst($schedule->status) }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <a href="{{ route('schedules.show', $schedule) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        <a href="{{ route('schedules.edit', $schedule) }}" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <span class="text-4xl block mb-2">📅</span>
                        No schedules found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $schedules->links() }}
</div>
@endsection
