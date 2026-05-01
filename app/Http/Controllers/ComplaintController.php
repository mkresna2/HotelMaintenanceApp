<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Services\ComplaintService;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ComplaintController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * Display a listing of complaints.
     * Implements GC-04, GC-09
     */
    public function index(Request $request): JsonResponse
    {
        $query = Complaint::with(['assignee', 'workOrder', 'reportedBy', 'logs'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->has('room_number')) {
            $query->where('room_number', $request->room_number);
        }
        
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }
        
        if ($request->has('escalated')) {
            $query->where('escalated', $request->boolean('escalated'));
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->where('reported_at', '>=', Carbon::parse($request->start_date));
        }
        
        if ($request->has('end_date')) {
            $query->where('reported_at', '<=', Carbon::parse($request->end_date));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('guest_name', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $complaints = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $complaints,
            'meta' => [
                'total' => $complaints->total(),
                'count' => $complaints->count(),
                'per_page' => $complaints->perPage(),
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created complaint.
     * Implements GC-01, GC-02, GC-03
     */
    public function store(StoreComplaintRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Handle file uploads if any
            if ($request->hasFile('attachments')) {
                $validated['attachments'] = $request->file('attachments');
            }

            $complaint = $this->complaintService->createComplaint($validated);

            return response()->json([
                'success' => true,
                'message' => 'Complaint logged successfully. Work order auto-generated.',
                'data' => $complaint->load(['workOrder', 'assignee', 'logs']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create complaint.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified complaint.
     */
    public function show(Complaint $complaint): JsonResponse
    {
        $complaint->load(['workOrder', 'assignee', 'reportedBy', 'logs', 'followUps.user']);

        return response()->json([
            'success' => true,
            'data' => $complaint,
        ]);
    }

    /**
     * Update the specified complaint.
     * Implements GC-04, GC-05, GC-06, GC-07
     */
    public function update(UpdateComplaintRequest $request, Complaint $complaint): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Update status if provided
            if (isset($validated['status'])) {
                $note = $validated['note'] ?? null;
                $this->complaintService->updateComplaintStatus($complaint, $validated['status'], $note);
            }

            // Update priority if provided
            if (isset($validated['priority'])) {
                $complaint->priority = $validated['priority'];
                $complaint->save();
            }

            // Add follow-up if provided
            if (isset($validated['follow_up_note'])) {
                $this->complaintService->addFollowUp($complaint, [
                    'note' => $validated['follow_up_note'],
                    'type' => $validated['follow_up_type'] ?? 'internal',
                ]);
            }

            // Record satisfaction rating if provided
            if (isset($validated['satisfaction_rating'])) {
                $complaint->satisfaction_rating = $validated['satisfaction_rating'];
                $complaint->satisfaction_comment = $validated['satisfaction_comment'] ?? null;
                $complaint->save();
            }

            // Update other fields
            $otherFields = ['guest_name', 'guest_contact', 'description', 'category', 'assigned_to'];
            foreach ($otherFields as $field) {
                if (isset($validated[$field])) {
                    $complaint->$field = $validated[$field];
                }
            }
            
            if (!$complaint->wasRecentlyCreated) {
                $complaint->save();
            }

            $complaint->load(['workOrder', 'assignee', 'logs', 'followUps.user']);

            return response()->json([
                'success' => true,
                'message' => 'Complaint updated successfully.',
                'data' => $complaint,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update complaint.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get complaint analytics.
     * Implements GC-09
     */
    public function analytics(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : now()->subMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : now();

        $analytics = $this->complaintService->getComplaintAnalytics($startDate, $endDate);

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
     * Get complaints by room history.
     * Implements GC-10
     */
    public function roomHistory(string $roomNumber): JsonResponse
    {
        $complaints = Complaint::where('room_number', $roomNumber)
            ->with(['workOrder', 'assignee'])
            ->orderBy('reported_at', 'desc')
            ->limit(50)
            ->get();

        $summary = [
            'total_complaints' => $complaints->count(),
            'by_category' => $complaints->groupBy('category')->map->count(),
            'avg_resolution_time' => $complaints->whereNotNull('resolved_at')
                ->avg(fn($c) => $c->reported_at->diffInMinutes($c->resolved_at)),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'room_number' => $roomNumber,
                'complaints' => $complaints,
                'summary' => $summary,
            ],
        ]);
    }
}
