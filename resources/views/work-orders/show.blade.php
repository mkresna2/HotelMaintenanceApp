<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Work Order Details') }}
            </h2>
            <div class="flex space-x-2">
                @can('update', $workOrder)
                    <a href="{{ route('work-orders.edit', $workOrder) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Edit Work Order
                    </a>
                @endcan
                <a href="{{ route('work-orders.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Status Timeline -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Status Timeline</h3>
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        <div class="relative space-y-4">
                            @foreach(['open', 'in_progress', 'pending_parts', 'resolved', 'closed'] as $status)
                                @php
                                    $isCompleted = array_search($status, array_keys($statuses)) <= array_search($workOrder->status, array_keys($statuses));
                                    $label = ucfirst(str_replace('_', ' ', $status));
                                @endphp
                                <div class="flex items-start">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center z-10 
                                        {{ $isCompleted ? 'bg-green-500' : 'bg-gray-300' }}">
                                        @if($isCompleted)
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium {{ $isCompleted ? 'text-gray-900' : 'text-gray-500' }}">
                                            {{ $label }}
                                        </p>
                                        @if($isCompleted && isset($workOrder->logs[$status]))
                                            <p class="text-xs text-gray-500">
                                                {{ $workOrder->logs[$status]->created_at->format('M d, Y H:i') }}
                                                @if($workOrder->logs[$status]->user)
                                                    by {{ $workOrder->logs[$status]->user->name }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Main Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Work Order Details</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">WO Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $workOrder->wo_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $workOrder->priority === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $workOrder->priority === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                        {{ $workOrder->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $workOrder->priority === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                        {{ ucfirst($workOrder->priority) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($workOrder->type) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $workOrder->technician?->user->name ?? 'Unassigned' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">SLA Deadline</dt>
                                <dd class="mt-1 text-sm {{ $workOrder->isOverdue() ? 'text-red-600 font-bold' : 'text-gray-900' }}">
                                    {{ $workOrder->sla_deadline?->format('M d, Y H:i') ?? 'N/A' }}
                                    @if($workOrder->isOverdue())
                                        (Overdue by {{ $workOrder->sla_deadline->diffForHumans() }})
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Location & Asset -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Location & Asset</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Room/Location</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $workOrder->location_name ?? 'N/A' }}</dd>
                            </div>
                            @if($workOrder->asset)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Asset</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a href="{{ route('assets.show', $workOrder->asset) }}" class="text-blue-600 hover:underline">
                                            {{ $workOrder->asset->name }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Asset Health</dt>
                                    <dd class="mt-1">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                                            <div class="h-2.5 rounded-full 
                                                {{ $workOrder->asset->health_score >= 80 ? 'bg-green-600' : '' }}
                                                {{ $workOrder->asset->health_score >= 50 && $workOrder->asset->health_score < 80 ? 'bg-yellow-600' : '' }}
                                                {{ $workOrder->asset->health_score < 50 ? 'bg-red-600' : '' }}" 
                                                style="width: {{ $workOrder->asset->health_score }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $workOrder->asset->health_score }}/100</span>
                                    </dd>
                                </div>
                            @endif
                            @if($workOrder->complaint)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Linked Complaint</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a href="{{ route('complaints.show', $workOrder->complaint) }}" class="text-blue-600 hover:underline">
                                            #{{ $workOrder->complaint->complaint_number }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Description & Notes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Description & Notes</h3>
                    <div class="prose max-w-none text-gray-700">
                        {{ $workOrder->description }}
                    </div>
                    
                    @if($workOrder->notes)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Technician Notes</h4>
                            <p class="text-sm text-gray-700">{{ $workOrder->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Attachments -->
            @if($workOrder->attachments->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Attachments</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($workOrder->attachments as $attachment)
                                <div class="relative group">
                                    @if(Str::contains($attachment->mime_type, 'image'))
                                        <img src="{{ Storage::url($attachment->file_path) }}" 
                                             class="w-full h-32 object-cover rounded-md cursor-pointer"
                                             onclick="openModal('{{ Storage::url($attachment->file_path) }}')">
                                    @else
                                        <div class="w-full h-32 bg-gray-100 rounded-md flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <p class="mt-2 text-xs text-gray-500 truncate">{{ $attachment->original_name }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Cost Breakdown -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Cost Breakdown</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-md">
                            <dt class="text-sm font-medium text-gray-500">Labor Cost</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">${{ number_format($workOrder->labor_cost, 2) }}</dd>
                            <p class="text-xs text-gray-500">{{ $workOrder->labor_hours ?? 0 }} hours</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <dt class="text-sm font-medium text-gray-500">Parts Cost</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">${{ number_format($workOrder->parts_cost, 2) }}</dd>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <dt class="text-sm font-medium text-gray-500">Total Cost</dt>
                            <dd class="mt-1 text-lg font-semibold text-blue-600">${{ number_format($workOrder->total_cost, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center" onclick="closeModal()">
        <img id="modalImage" src="" class="max-w-full max-h-full rounded-md">
    </div>

    <script>
        function openModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.remove('hidden');
        }
        function closeModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }
    </script>
</x-app-layout>
