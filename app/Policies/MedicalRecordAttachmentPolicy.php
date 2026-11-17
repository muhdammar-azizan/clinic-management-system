<?php

namespace App\Policies;

use App\Models\MedicalRecordAttachment;
use App\Models\User;

class MedicalRecordAttachmentPolicy
{
    /**
     * The treating doctor can download every file of their record.
     * The patient can download only prescription files. Files of deleted records are not available.
     */
    public function download(User $user, MedicalRecordAttachment $attachment): bool
    {
        $record = $attachment->record;

        if ($record === null) {
            return false;
        }

        if ($user->isDoctor()) {
            return $record->doctor_id === $user->id;
        }

        return $user->isPatient()
            && $record->patient_id === $user->id
            && $attachment->isPrescription();
    }
}
