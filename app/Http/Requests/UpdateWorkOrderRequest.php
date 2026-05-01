<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|min:10|max:2000',
            'type' => 'nullable|string|in:corrective,preventive,emergency,inspection',
            'priority' => 'nullable|string|in:critical,high,medium,low',
            'status' => 'nullable|string|in:open,in_progress,pending_parts,resolved,closed,cancelled',
            'location_type' => 'nullable|string|in:room,asset,area,general',
            'location_value' => 'nullable|string|max:50',
            'asset_id' => 'nullable|exists:assets,id',
            'assigned_to' => 'nullable|exists:technicians,id',
            'estimated_hours' => 'nullable|numeric|min:0.5|max:100',
            
            // For status updates
            'note' => 'nullable|string|max:2000',
            
            // For logging work
            'time_spent' => 'nullable|numeric|min:0|max:24',
            'parts_used' => 'nullable|array',
            'parts_used.*.spare_part_id' => 'required_with:parts_used|exists:spare_parts,id',
            'parts_used.*.quantity' => 'required_with:parts_used|integer|min:1',
            'parts_used.*.unit_cost' => 'nullable|numeric|min:0',
            
            // For attachments
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,mp4,mov|max:10240',
            'file_type' => 'nullable|string|in:photo,video,document',
            'attachment_description' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status. Must be one of: open, in_progress, pending_parts, resolved, closed, cancelled.',
            'time_spent.min' => 'Time spent cannot be negative.',
            'time_spent.max' => 'Time spent cannot exceed 24 hours in a single log.',
            'parts_used.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check valid status transitions if status is being updated
            if ($this->has('status') && $this->has('work_order')) {
                $workOrder = $this->route('workOrder') ?? $this->work_order;
                if ($workOrder) {
                    $validTransitions = [
                        'open' => ['in_progress', 'pending_parts', 'cancelled'],
                        'in_progress' => ['pending_parts', 'resolved', 'open'],
                        'pending_parts' => ['in_progress', 'cancelled'],
                        'resolved' => ['closed', 'in_progress'],
                    ];
                    
                    $currentStatus = $workOrder->status;
                    $newStatus = $this->input('status');
                    
                    if (isset($validTransitions[$currentStatus]) && 
                        !in_array($newStatus, $validTransitions[$currentStatus])) {
                        $validator->errors()->add(
                            'status',
                            "Cannot transition from '{$currentStatus}' to '{$newStatus}'."
                        );
                    }
                }
            }
        });
    }
}
