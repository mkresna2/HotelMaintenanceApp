<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateWorkOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class WorkOrderController extends Controller
{
    protected WorkOrderService $workOrderService;

    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /**
     * Display a listing of work orders.
     * Implements WO-04, WO-05, RPT-02
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkOrder::with(['assignee', 'asset', 'complaint', 'createdBy', 'logs'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('location_type')) {
            $query->where('location_type', $request->location_type);
        }
        
        if ($request->has('location_value')) {
            $query->where('location_value', $request->location_value);
        }
        
        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }
        
        if ($request->has('complaint_id')) {
            $query->where('complaint_id', $request->complaint_id);
        }

        // Overdue filter
        if ($request->boolean('overdue')) {
            $query->where('sla_due_at', '<', now())
                ->whereIn('status', ['open', 'in_progress', 'pending_parts']);
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->start_date));
        }
        
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->end_date));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('work_order_id', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $workOrders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $workOrders,
            'meta' => [
                'total' => $workOrders->total(),
                'count' => $workOrders->count(),
                'per_page' => $workOrders->perPage(),
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created work order.
     * Implements WO-01, WO-02, WO-03, WO-04
     */
    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Check for potential duplicates (WO-10)
            if (isset($validated['description'], $validated['location_value'])) {
                $duplicates = $this->workOrderService->detectDuplicates(
                    $validated['description'],
                    $validated['location_value'],
                    $validated['asset_id'] ?? null
                );
                
                if ($duplicates['has_potential_duplicates']) {
                    // Log warning but continue creation
                    logger()->warning('Potential duplicate work order detected', [
                        'duplicates' => $duplicates['count'],
                    ]);
                }
            }

            $workOrder = $this->workOrderService->createWorkOrder($validated);

            return response()->json([
                'success' => true,
                'message' => 'Work order created successfully.',
                'data' => $workOrder->load(['assignee', 'asset', 'complaint', 'logs']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create work order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified work order.
     */
    public function show(WorkOrder $workOrder): JsonResponse
    {
        $workOrder->load(['assignee.user', 'asset', 'complaint', 'createdBy', 'logs.user', 'partsUsed', 'attachments.uploader']);

        // Get location history (WO-09)
        $history = null;
        if ($workOrder->location_type && $workOrder->location_value) {
            $history = $this->workOrderService->getHistoryByLocation(
                $workOrder->location_type,
                $workOrder->location_value,
                10
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'work_order' => $workOrder,
                'location_history' => $history,
            ],
        ]);
    }

    /**
     * Update the specified work order.
     * Implements WO-03, WO-05, WO-06, WO-07
     */
    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Handle status update
            if (isset($validated['status'])) {
                $note = $validated['note'] ?? null;
                $this->workOrderService->updateStatus($workOrder, $validated['status'], $note);
            }

            // Handle assignment
            if (isset($validated['assigned_to'])) {
                $note = $validated['note'] ?? null;
                $this->workOrderService->assignWorkOrder($workOrder, $validated['assigned_to'], $note);
            }

            // Log work (time spent and parts)
            if (isset($validated['time_spent']) || !empty($validated['parts_used'])) {
                $this->workOrderService->logWork($workOrder, [
                    'time_spent' => $validated['time_spent'] ?? 0,
                    'note' => $validated['note'] ?? null,
                    'parts_used' => $validated['parts_used'] ?? [],
                ]);
            }

            // Handle attachment upload
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('work-orders/' . $workOrder->id, 'public');
                
                $this->workOrderService->addAttachment(
                    $workOrder,
                    $path,
                    $validated['file_type'] ?? 'photo',
                    $validated['attachment_description'] ?? null
                );
            }

            // Update other fields
            $updatableFields = ['title', 'description', 'type', 'priority', 'location_type', 'location_value', 'asset_id', 'estimated_hours'];
            foreach ($updatableFields as $field) {
                if (isset($validated[$field])) {
                    $workOrder->$field = $validated[$field];
                }
            }
            
            if (!$workOrder->wasRecentlyCreated) {
                $workOrder->save();
            }

            $workOrder->load(['assignee.user', 'asset', 'complaint', 'logs.user', 'partsUsed', 'attachments.uploader']);

            return response()->json([
                'success' => true,
                'message' => 'Work order updated successfully.',
                'data' => $workOrder,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid operation.',
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update work order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign work order to technician.
     * Implements WO-03
     */
    public function assign(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $request->validate([
            'technician_id' => 'required|exists:technicians,id',
            'note' => 'nullable|string|max:2000',
        ]);

        try {
            $workOrder = $this->workOrderService->assignWorkOrder(
                $workOrder,
                $request->technician_id,
                $request->note
            );

            return response()->json([
                'success' => true,
                'message' => 'Work order assigned successfully.',
                'data' => $workOrder->load(['assignee.user']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign work order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get work order analytics.
     * Implements RPT-03
     */
    public function analytics(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : now()->subMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : now();

        $analytics = $this->workOrderService->getAnalytics($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * Get technician workload for balancing.
     */
    public function technicianWorkload(Request $request): JsonResponse
    {
        $date = $request->has('date') ? Carbon::parse($request->date) : now();
        
        $workload = $this->workOrderService->getTechnicianWorkload($date);

        return response()->json([
            'success' => true,
            'data' => $workload,
            'date' => $date->toDateString(),
        ]);
    }

    /**
     * Get work order history by location.
     * Implements WO-09
     */
    public function locationHistory(Request $request): JsonResponse
    {
        $request->validate([
            'location_type' => 'required|string|in:room,asset,area,general',
            'location_value' => 'required|string|max:50',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $history = $this->workOrderService->getHistoryByLocation(
            $request->location_type,
            $request->location_value,
            $request->get('limit', 50)
        );

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
