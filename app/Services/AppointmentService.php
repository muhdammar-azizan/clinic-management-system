<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

/**
 * Status changes that also keep the roster slot in sync.
 * Every method locks the appointment row so two requests cannot change it at once.
 */
class AppointmentService
{
    /**
     * Cancel a booked appointment and release its slot.
     * Patients may only cancel before the slot starts. Doctors may cancel at any time (for example a no-show).
     *
     * @return string|null An error message, or null on success.
     */
    public function cancel(Appointment $appointment, bool $onlyBeforeStart = false): ?string
    {
        return DB::transaction(function () use ($appointment, $onlyBeforeStart) {
            $appointment = $this->lock($appointment);

            if (! $appointment->isBooked()) {
                return 'Only booked appointments can be cancelled.';
            }

            if ($onlyBeforeStart && ! $appointment->slot->startsAt()->isFuture()) {
                return 'Only upcoming appointments can be cancelled.';
            }

            $appointment->update(['status' => AppointmentStatus::Cancelled]);
            $appointment->slot->update(['status' => SlotStatus::Available]);

            return null;
        });
    }

    /**
     * Mark a booked appointment as completed. The slot stays occupied.
     *
     * @return string|null An error message, or null on success.
     */
    public function complete(Appointment $appointment): ?string
    {
        return DB::transaction(function () use ($appointment) {
            $appointment = $this->lock($appointment);

            if (! $appointment->isBooked()) {
                return 'Only booked appointments can be completed.';
            }

            if ($appointment->slot->startsAt()->isFuture()) {
                return 'An appointment can only be completed after it has started.';
            }

            $appointment->update(['status' => AppointmentStatus::Completed]);

            return null;
        });
    }

    /**
     * Delete an appointment. A booked one gives its slot back first.
     */
    public function delete(Appointment $appointment): void
    {
        DB::transaction(function () use ($appointment) {
            $appointment = $this->lock($appointment);

            if ($appointment->isBooked()) {
                $appointment->slot->update(['status' => SlotStatus::Available]);
            }

            $appointment->delete();
        });
    }

    private function lock(Appointment $appointment): Appointment
    {
        return Appointment::with('slot')->lockForUpdate()->findOrFail($appointment->id);
    }
}
