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
                // Skipped for an impossible date, which the slot_date rule already reports.
                function (string $attribute, mixed $value, \Closure $fail) use ($slot) {
                    if (! $this->isRealDate($this->input('slot_date'))) {
                        return;
                    }

                    $taken = RosterSlot::query()
                        ->where('doctor_id', $this->input('doctor_id'))
                        ->where('slot_date', $this->input('slot_date'))
                        ->where('start_time', $value)
                        ->when($slot, fn ($query) => $query->whereKeyNot($slot->id))
                        ->exists();

                    if ($taken) {
                        $fail('This doctor already has a slot at that date and time.');
                    }
                },
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                function (string $attribute, mixed $value, \Closure $fail) use ($slot) {
                    if ($this->overlapsAnotherSlot($slot, $value)) {
                        $fail('This doctor already has a slot that overlaps with that time range.');
                    }
                },
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

    /**
     * True for a date that exists, such as 2026-02-28. A date like 2026-13-45 has the right
     * shape but MySQL refuses it with an error, so it must never reach a query.
     */
    private function isRealDate(mixed $date): bool
    {
        if (! is_string($date)) {
            return false;
        }

        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * Another slot of the same doctor on the same day covers part of start-end.
     * An old slot without an end time counts as an instant at its start time.
     */
    private function overlapsAnotherSlot(?RosterSlot $slot, string $end): bool
    {
        $doctor = $this->input('doctor_id');
        $date = $this->input('slot_date');
        $start = $this->input('start_time');

        // Missing or badly formatted input is reported by the other rules.
        if (! $doctor || ! $this->isRealDate($date) || ! preg_match('/^\d{2}:\d{2}$/', (string) $start)) {
            return false;
        }

        return RosterSlot::query()
            ->where('doctor_id', $doctor)
            ->where('slot_date', $date)
            ->when($slot, fn ($query) => $query->whereKeyNot($slot->id))
            ->where('start_time', '<', $end)
            ->where(fn ($query) => $query
                ->where('end_time', '>', $start)
                ->orWhere(fn ($legacy) => $legacy->whereNull('end_time')->where('start_time', '>=', $start)))
            ->exists();
    }

    public function messages(): array
    {
        return [
            'doctor_id.exists' => 'Please choose a valid doctor.',
            'end_time.after' => 'The end time must be later than the start time.',
        ];
    }
}
