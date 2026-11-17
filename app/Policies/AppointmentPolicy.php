<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

/**
 * Who may act on a single appointment. The admin can list appointments but never change them,
 * so no ability here returns true for the admin.
 */
class AppointmentPolicy
{
    /**
     * The patient who booked it may cancel it.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->isPatient() && $appointment->patient_id === $user->id;
    }

    /**
     * The doctor whose slot was booked may view, update, complete and delete it.
     */
    public function manage(User $user, Appointment $appointment): bool
    {
        return $user->isDoctor() && $appointment->slot->doctor_id === $user->id;
    }

    /**
     * Only the treating doctor may write the medical record for it.
     */
    public function writeRecord(User $user, Appointment $appointment): bool
    {
        return $this->manage($user, $appointment);
    }
}
