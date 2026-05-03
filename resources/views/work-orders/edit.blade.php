<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Work Order') }} #{{ $workOrder->wo_number }}
            </h2>
            <a href="{{ route('work-orders.show', $workOrder) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                            <strong class="font-bold">Error!</strong>
                            <ul class="mt-2 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('work-orders.update', $workOrder) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Status & Priority -->
                            <div class="col-span-1">
                                <label class="block font-medium text-sm text-gray-700">Status</label>
                                <select name="status" class="border-gray-300 rounded-md shadow-sm w-full mt-1">
                                    @foreach(['Open', 'In Progress', 'Pending Parts', 'Resolved', 'Closed'] as $status)
                                        <option value="{{ $status }}" {{ $workOrder->status == $status ? 'selected' : '' }}>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-span-1">
                                <label class="block font-medium text-sm text-gray-700">Priority</label>
                                <select name="priority" class="border-gray-300 rounded-md shadow-sm w-full mt-1">
                                    @foreach(['Low', 'Medium', 'High', 'Critical'] as $prio)
                                        <option value="{{ $prio }}" {{ $workOrder->priority == $prio ? 'selected' : '' }}>
                                            {{ $prio }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Technician Assignment -->
                            <div class="col-span-1">
                                <label class="block font-medium text-sm text-gray-700">Assign To</label>
                                <select name="technician_id" class="border-gray-300 rounded-md shadow-sm w-full mt-1">
                                    <option value="">-- Unassigned --</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ $workOrder->technician_id == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- SLA Timer Display (Read Only) -->
                            <div class="col-span-1">
                                <label class="block font-medium text-sm text-gray-700">SLA Deadline</label>
                                <div class="mt-1 p-2 bg-gray-50 border rounded {{ $workOrder->isOverdue() ? 'bg-red-50 border-red-300' : '' }}">
                                    <span class="font-mono">{{ $workOrder->sla_deadline ? $workOrder->sla_deadline->format('M d, Y H:i') : 'N/A' }}</span>
                                    @if($workOrder->isOverdue())
                                        <span class="text-red-600 text-xs font-bold ml-2">OVERDUE</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-span-2">
                                <label class="block font-medium text-sm text-gray-700">Description / Notes</label>
                                <textarea name="description" rows="4" class="border-gray-300 rounded-md shadow-sm w-full mt-1">{{ old('description', $workOrder->description) }}</textarea>
                            </div>

                            <!-- Labor & Parts -->
                            <div class="col-span-1">
                                <label class="block font-medium text-sm text-gray-700">Labor Hours</label>
                                <input type="number" step="0.5" name="labor_hours" value="{{ old('labor_hours', $workOrder->labor_hours) }}" class="border-gray-300 rounded-md shadow-sm w-full mt-1">
                            </div>
                            <div class="col-span-1">
                                <label class="block font-medium text-sm text-gray-700">Parts Cost ($)</label>
                                <input type="number" step="0.01" name="parts_cost" value="{{ old('parts_cost', $workOrder->parts_cost) }}" class="border-gray-300 rounded-md shadow-sm w-full mt-1">
                            </div>

                            <!-- Attachments -->
                            <div class="col-span-2">
                                <label class="block font-medium text-sm text-gray-700">Add Attachment</label>
                                <input type="file" name="attachment" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                                <p class="text-xs text-gray-500 mt-1">Existing attachments: {{ $workOrder->attachments->count() }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <a href="{{ route('work-orders.show', $workOrder) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Work Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
