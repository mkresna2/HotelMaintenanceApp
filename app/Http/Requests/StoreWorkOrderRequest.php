<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:10|max:2000',
            'type' => 'nullable|string|in:corrective,preventive,emergency,inspection',
            'priority' => 'nullable|string|in:critical,high,medium,low',
            'location_type' => 'nullable|string|in:room,asset,area,general',
            'location_value' => 'nullable|string|max:50',
            'asset_id' => 'nullable|exists:assets,id',
            'assigned_to' => 'nullable|exists:technicians,id',
            'created_by' => 'nullable|exists:users,id',
            'source' => 'nullable|string|in:manual,complaint,schedule,inspection',
            'complaint_id' => 'nullable|exists:complaints,id',
            'schedule_id' => 'nullable|exists:maintenance_schedules,id',
            'estimated_hours' => 'nullable|numeric|min:0.5|max:100',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,mp4,mov|max:10240',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Work order title is required.',
            'description.required' => 'Work order description is required.',
            'description.min' => 'Description must be at least 10 characters.',
            'type.in' => 'Type must be one of: corrective, preventive, emergency, inspection.',
            'priority.in' => 'Priority must be one of: critical, high, medium, low.',
            'estimated_hours.min' => 'Estimated hours must be at least 0.5.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'title',
            'description' => 'description',
            'estimated_hours' => 'estimated hours',
        ];
    }
}
