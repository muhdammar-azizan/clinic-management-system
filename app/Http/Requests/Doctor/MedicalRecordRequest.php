<?php

namespace App\Http\Requests\Doctor;

use App\Enums\ConditionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicalRecordRequest extends FormRequest
{
    /** Attachment limits, in kilobytes and count. */
    public const MAX_FILE_KB = 5120;

    public const MAX_FILES = 5;

    public function authorize(): bool
    {
        return $this->user()->isDoctor();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $file = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:'.self::MAX_FILE_KB];

        return [
            // Step 1: visit details
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'chief_complaint' => ['required', 'string', 'max:255'],
            'symptoms' => ['nullable', 'string', 'max:2000'],

            // Step 2: clinical assessment
            'diagnosis' => ['required', 'string', 'max:2000'],
            'treatment_plan' => ['nullable', 'string', 'max:2000'],
            'prescription' => ['nullable', 'string', 'max:2000'],
            'condition' => ['required', Rule::enum(ConditionStatus::class)],
            'follow_up_date' => ['nullable', 'date', 'after_or_equal:visit_date'],

            // Step 3: attachments
            'prescription_files' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'prescription_files.*' => $file,
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => $file,

            // Editing only: ids of existing attachments to remove.
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'prescription_files.*.mimes' => 'Prescription files must be PDF, JPG or PNG.',
            'attachments.*.mimes' => 'Attachments must be PDF, JPG or PNG.',
            'prescription_files.*.max' => 'Each file must be 5 MB or smaller.',
            'attachments.*.max' => 'Each file must be 5 MB or smaller.',
            'follow_up_date.after_or_equal' => 'The follow-up date cannot be before the visit date.',
        ];
    }
}
