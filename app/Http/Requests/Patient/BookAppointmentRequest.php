<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isPatient();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'roster_slot_id' => ['required', Rule::exists('roster_slots', 'id')],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'roster_slot_id.required' => 'Please choose a time slot.',
            'service_id.required' => 'Please choose a service.',
        ];
    }
}
