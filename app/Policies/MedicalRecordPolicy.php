<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

/**
 * Medical records are private to the treating doctor and the patient.
 * The admin gets no ability here: the admin overview only lists non-clinical facts.
 */
class MedicalRecordPolicy
{
    /**
     * The patient and the doctor who wrote it can read it.
     */
    public function view(User $user, MedicalRecord $record): bool
    {
        return $this->manage($user, $record)
            || ($user->isPatient() && $record->patient_id === $user->id);
    }

    /**
     * Only the doctor who wrote it can edit or delete it.
     */
    public function manage(User $user, MedicalRecord $record): bool
    {
        return $user->isDoctor() && $record->doctor_id === $user->id;
    }
}
