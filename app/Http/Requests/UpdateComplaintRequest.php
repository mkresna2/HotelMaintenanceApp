<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
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
            'status' => 'nullable|string|in:open,in_progress,pending_parts,resolved,closed',
            'priority' => 'nullable|string|in:critical,high,medium,low',
            'guest_name' => 'nullable|string|max:255',
            'guest_contact' => 'nullable|string|max:50',
            'description' => 'nullable|string|min:10|max:2000',
            'category' => 'nullable|string|in:plumbing,electrical,hvac,furniture,appliance,general',
            'assigned_to' => 'nullable|exists:technicians,id',
            'note' => 'nullable|string|max:2000',
            'follow_up_note' => 'nullable|string|max:2000',
            'follow_up_type' => 'nullable|string|in:internal,guest_communication',
            'satisfaction_rating' => 'nullable|integer|min:1|max:5',
            'satisfaction_comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status. Must be one of: open, in_progress, pending_parts, resolved, closed.',
            'satisfaction_rating.min' => 'Rating must be between 1 and 5.',
            'satisfaction_rating.max' => 'Rating must be between 1 and 5.',
        ];
    }
}
