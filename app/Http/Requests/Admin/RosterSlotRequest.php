<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Enums\SlotStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RosterSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $slot = $this->route('slot');

        return [
            'doctor_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', Role::Doctor->value),
            ],
            // New slots must be in the future. An existing slot may keep its past date.
            'slot_date' => ['required', 'date', $slot ? 'date' : 'after_or_equal:today'],
            'start_time' => [
                'required',
                'date_format:H:i',
                Rule::unique('roster_slots')
                    ->where('doctor_id', $this->input('doctor_id'))
                    ->where('slot_date', $this->input('slot_date'))
                    ->ignore($slot?->id),
            ],
            'status' => ['required', Rule::enum(SlotStatus::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.unique' => 'This doctor already has a slot at that date and time.',
            'doctor_id.exists' => 'Please choose a valid doctor.',
        ];
    }
}
