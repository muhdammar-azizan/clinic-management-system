<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecordAttachment>
 */
class MedicalRecordAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'type' => 'other',
            'path' => 'medical-records/'.fake()->uuid().'.pdf',
            'original_name' => 'report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 20480,
        ];
    }

    public function prescription(): static
    {
        return $this->state(['type' => 'prescription', 'original_name' => 'prescription.pdf']);
    }
}
