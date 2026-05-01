<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be refined with policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'guest_name' => 'nullable|string|max:255',
            'guest_contact' => 'nullable|string|max:50',
            'room_number' => 'required|string|max:20',
            'category' => 'nullable|string|in:plumbing,electrical,hvac,furniture,appliance,general',
            'description' => 'required|string|min:10|max:2000',
            'priority' => 'nullable|string|in:critical,high,medium,low',
            'channel' => 'nullable|string|in:front_desk,mobile,qr_code,pms',
            'reported_by' => 'nullable|exists:users,id',
            'asset_id' => 'nullable|exists:assets,id',
            'assigned_to' => 'nullable|exists:technicians,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,mp4,mov|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'room_number.required' => 'Room number is required to log a complaint.',
            'description.required' => 'Please describe the issue.',
            'description.min' => 'Description must be at least 10 characters.',
            'priority.in' => 'Priority must be one of: critical, high, medium, low.',
            'channel.in' => 'Invalid channel specified.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'guest_name' => 'guest name',
            'room_number' => 'room number',
            'description' => 'issue description',
        ];
    }
}
