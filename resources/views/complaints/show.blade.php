@extends('layouts.app')

@section('title', 'Complaint #' . $complaint->reference_number)

@section('content')
<div class="container-fluid" x-data="{ activeTab: 'details' }">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <span class="badge bg-{{ $complaint->status_color }} me-2">{{ $complaint->status }}</span>
                        Complaint #{{ $complaint->reference_number }}
                    </h2>
                    <p class="text-muted mb-0">Created {{ $complaint->created_at->diffForHumans() }}</p>
                </div>
                <div>
                    @can('update', $complaint)
                        <a href="{{ route('complaints.edit', $complaint) }}" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Complaint
                        </a>
                    @endcan
                    <a href="{{ route('complaints.index') }}" class="btn btn-outline-secondary">Back to List</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Timeline -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Status Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($complaint->statusHistory as $index => $history)
                        <div class="timeline-item {{ $index === count($complaint->statusHistory) - 1 ? 'active' : '' }}">
                            <div class="timeline-marker">
                                <i class="bi bi-{{ $history['icon'] }}"></i>
                            </div>
                            <div class="timeline-content">
                                <h6>{{ $history['status'] }}</h6>
                                <p class="text-muted small">{{ $history['timestamp'] }}</p>
                                @if(isset($history['note']))
                                    <p class="mb-0">{{ $history['note'] }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Details -->
        <div class="col-lg-8">
            <!-- Guest & Issue Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Guest & Issue Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Guest Name</label>
                            <p class="mb-0 fw-bold">{{ $complaint->guest_name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Room Number</label>
                            <p class="mb-0 fw-bold">{{ $complaint->room_number }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Contact</label>
                            <p class="mb-0">{{ $complaint->guest_contact }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Category</label>
                            <p class="mb-0">
                                <span class="badge bg-info">{{ $complaint->category }}</span>
                            </p>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small">Description</label>
                            <p class="mb-0">{{ $complaint->description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked Work Order -->
            @if($complaint->workOrder)
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Linked Work Order</h5>
                    <a href="{{ route('work-orders.show', $complaint->workOrder) }}" class="btn btn-sm btn-outline-primary">
                        View Work Order
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="text-muted small">WO Number</label>
                            <p class="mb-0 fw-bold">{{ $complaint->workOrder->reference_number }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Assigned To</label>
                            <p class="mb-0">
                                @if($complaint->workOrder->assignedTo)
                                    {{ $complaint->workOrder->assignedTo->name }}
                                @else
                                    <span class="text-warning">Unassigned</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small">Priority</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $complaint->workOrder->priority_color }}">
                                    {{ $complaint->workOrder->priority }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label class="text-muted small">Status</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $complaint->workOrder->status_color }}">
                                    {{ $complaint->workOrder->status }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Attachments -->
            @if($complaint->attachments->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Attachments ({{ $complaint->attachments->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($complaint->attachments as $attachment)
                        <div class="col-md-4 col-sm-6">
                            <div class="card h-100">
                                @if($attachment->isImage())
                                    <img src="{{ $attachment->getUrl() }}" class="card-img-top" alt="{{ $attachment->file_name }}" style="height: 200px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-file-earmark-text display-4 text-muted"></i>
                                    </div>
                                @endif
                                <div class="card-body p-2 text-center">
                                    <p class="small mb-1 text-truncate">{{ $attachment->file_name }}</p>
                                    <a href="{{ $attachment->getUrl() }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Follow-ups -->
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Follow-ups</h5>
                    @if($complaint->canAddFollowUp())
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addFollowUpModal">
                            <i class="bi bi-plus"></i> Add Follow-up
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @forelse($complaint->followUps as $followUp)
                    <div class="border-bottom pb-3 mb-3 last-follow-up">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>{{ $followUp->user->name }}</strong>
                            <small class="text-muted">{{ $followUp->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="mb-2">{{ $followUp->notes }}</p>
                        @if($followUp->satisfaction_score)
                            <div class="mt-2">
                                <label class="small text-muted">Satisfaction Score:</label>
                                <div class="text-warning">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star{{ $i <= $followUp->satisfaction_score ? '-fill' : '' }}"></i>
                                    @endfor
                                </div>
                            </div>
                        @endif
                    </div>
                    @empty
                        <p class="text-muted mb-0">No follow-ups yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- SLA Timer -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">SLA Status</h5>
                </div>
                <div class="card-body text-center">
                    @if($complaint->isWithinSla())
                        <div class="text-success mb-2">
                            <i class="bi bi-check-circle display-4"></i>
                        </div>
                        <h5 class="text-success">Within SLA</h5>
                        <p class="text-muted small">Expires in {{ $complaint->getSlaRemainingTime() }}</p>
                    @else
                        <div class="text-danger mb-2">
                            <i class="bi bi-exclamation-triangle display-4"></i>
                        </div>
                        <h5 class="text-danger">SLA Breached</h5>
                        <p class="text-muted small">Breached {{ $complaint->getSlaBreachedTime() }} ago</p>
                    @endif
                    <hr>
                    <div class="text-start">
                        <small class="text-muted">Priority:</small>
                        <p><span class="badge bg-{{ $complaint->priority_color }}">{{ $complaint->priority }}</span></p>
                        <small class="text-muted">Target Resolution:</small>
                        <p>{{ $complaint->sla_due_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Activity Log</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @foreach($complaint->activityLog as $activity)
                    <div class="mb-3 pb-2 border-bottom">
                        <small class="text-muted d-block">{{ $activity['timestamp'] }}</small>
                        <p class="mb-0 small">{{ $activity['description'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Follow-up Modal -->
<div class="modal fade" id="addFollowUpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('complaints.followups.store', $complaint) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Follow-up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Satisfaction Score (Optional)</label>
                        <select name="satisfaction_score" class="form-select">
                            <option value="">Not rated</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Very Poor</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Follow-up</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
    border-left: 2px solid #e9ecef;
}
.timeline-item:last-child {
    border-left-color: transparent;
}
.timeline-item.active {
    border-left-color: #0d6efd;
}
.timeline-marker {
    position: absolute;
    left: -36px;
    top: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}
.timeline-item.active .timeline-marker {
    border-color: #0d6efd;
    color: #0d6efd;
}
.timeline-content {
    padding-left: 15px;
}
</style>
@endpush

@endsection
