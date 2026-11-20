<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\RosterSlot;
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
            // The date and time must not be in the past. An existing slot may keep its
            // own past date and time (for example to fix its notes), but cannot be moved to one.
            'slot_date' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($slot) {
                    if (! $this->isUnchanged($slot) && $value < today()->toDateString()) {
                        $fail('Past schedule slots are not allowed. Please choose today or a future date.');
                    }
                },
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail) use ($slot) {
                    $date = $this->input('slot_date');

                    // Only today needs a time check. Earlier dates are rejected by the date rule.
                    if (! $this->isUnchanged($slot) && $date === today()->toDateString() && $value <= now()->format('H:i')) {
                        $fail('That start time has already passed today. Please choose a later time.');
                    }
                },
                Rule::unique('roster_slots')
                    ->where('doctor_id', $this->input('doctor_id'))
                    ->where('slot_date', $this->input('slot_date'))
                    ->ignore($slot?->id),
            ],
            'status' => ['required', Rule::enum(SlotStatus::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * True when editing a slot and its date and time are not being changed.
     */
    private function isUnchanged(?RosterSlot $slot): bool
    {
        return $slot !== null
            && $this->input('slot_date') === $slot->slot_date->toDateString()
            && $this->input('start_time') === $slot->start_time_label;
    }

    public function messages(): array
    {
        return [
            'start_time.unique' => 'This doctor already has a slot at that date and time.',
            'doctor_id.exists' => 'Please choose a valid doctor.',
        ];
    }
}
