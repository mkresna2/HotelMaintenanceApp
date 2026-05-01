@extends('layouts.app')

@section('title', 'Reports - HotelMaint Pro')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reports & Analytics</h1>
            <p class="text-gray-600">Comprehensive maintenance performance insights</p>
        </div>
        <div class="space-x-2">
            <button onclick="exportReport('work-orders')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                📊 Export Work Orders
            </button>
            <button onclick="exportReport('complaints')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                📊 Export Complaints
            </button>
        </div>
    </div>
</div>

<!-- Work Order Statistics -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold">Work Order Statistics</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <p class="text-3xl font-bold text-blue-600">{{ $workOrderStats['total'] }}</p>
                <p class="text-sm text-gray-600 mt-1">Total Work Orders</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-3xl font-bold text-green-600">{{ $workOrderStats['avg_resolution_time'] }}</p>
                <p class="text-sm text-gray-600 mt-1">Avg Resolution Time</p>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <p class="text-3xl font-bold text-purple-600">{{ $workOrderStats['mttr'] }}</p>
                <p class="text-sm text-gray-600 mt-1">MTTR</p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <p class="text-3xl font-bold text-yellow-600">{{ $complaintStats['resolution_rate'] }}</p>
                <p class="text-sm text-gray-600 mt-1">Resolution Rate</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-medium mb-3">By Status</h3>
                <div class="space-y-2">
                    @foreach($workOrderStats['by_status'] as $status => $count)
                        <div class="flex justify-between items-center">
                            <span class="status-badge status-{{ $status }}">{{ ucfirst($status) }}</span>
                            <span class="font-medium">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <h3 class="font-medium mb-3">By Priority</h3>
                <div class="space-y-2">
                    @foreach($workOrderStats['by_priority'] as $priority => $count)
                        <div class="flex justify-between items-center">
                            <span class="capitalize">{{ $priority }}</span>
                            <span class="font-medium">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complaint Statistics -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold">Complaint Statistics</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-medium mb-3">By Category</h3>
                <div class="space-y-2">
                    @foreach($complaintStats['by_category'] as $category => $count)
                        <div class="flex justify-between items-center">
                            <span class="capitalize">{{ str_replace('_', ' ', $category) }}</span>
                            <span class="font-medium">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div>
                <h3 class="font-medium mb-3">Top Problem Rooms</h3>
                <div class="space-y-2">
                    @foreach($complaintStats['by_room'] as $room)
                        <div class="flex justify-between items-center">
                            <span>Room {{ $room->room_number }}</span>
                            <span class="font-medium">{{ $room->count }} complaints</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Asset Statistics -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b">
        <h2 class="text-lg font-semibold">Asset Statistics</h2>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-3xl font-bold text-green-600">{{ $assetStats['total'] }}</p>
                <p class="text-sm text-gray-600 mt-1">Total Assets</p>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <p class="text-3xl font-bold text-green-600">{{ $assetStats['by_health']['healthy'] }}</p>
                <p class="text-sm text-gray-600 mt-1">Healthy</p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <p class="text-3xl font-bold text-yellow-600">{{ $assetStats['by_health']['warning'] }}</p>
                <p class="text-sm text-gray-600 mt-1">Warning</p>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-lg">
                <p class="text-3xl font-bold text-red-600">{{ $assetStats['by_health']['critical'] }}</p>
                <p class="text-sm text-gray-600 mt-1">Critical</p>
            </div>
        </div>

        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <span class="text-xl">⚠️</span>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>{{ $assetStats['warranty_expiring_soon'] }}</strong> assets have warranties expiring in the next 30 days
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport(type) {
    window.location.href = '{{ route("reports.export") }}?type=' + type;
}
</script>
@endsection
