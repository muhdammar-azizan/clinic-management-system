<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Patients can read their own records and download prescriptions. They cannot change anything.
 */
class MedicalRecordController extends Controller
{
    public function index(Request $request): View
    {
        $records = $request->user()->medicalRecords()
            ->with('doctor.doctorProfile.service')
            ->withCount(['attachments as prescriptions_count' => fn ($q) => $q->where('type', MedicalRecordAttachment::TYPE_PRESCRIPTION)])
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('patient.records.index', ['records' => $records]);
    }

    public function show(Request $request, MedicalRecord $record): View
    {
        $this->authorizeOwnership($request, $record);

        $record->load('doctor.doctorProfile.service');

        return view('patient.records.show', [
            'record' => $record,
            // Only prescription files are shared with the patient. Other attachments stay with the doctor.
            'prescriptions' => $record->attachments()->where('type', MedicalRecordAttachment::TYPE_PRESCRIPTION)->get(),
        ]);
    }

    public function download(Request $request, MedicalRecordAttachment $attachment): StreamedResponse
    {
        $this->authorizeOwnership($request, $attachment->record);
        abort_unless($attachment->isPrescription(), 403);
        abort_unless(Storage::exists($attachment->path), 404);

        return Storage::download($attachment->path, $attachment->original_name);
    }

    private function authorizeOwnership(Request $request, MedicalRecord $record): void
    {
        abort_unless($record->patient_id === $request->user()->id, 403);
    }
}
