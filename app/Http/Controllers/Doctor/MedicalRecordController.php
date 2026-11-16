<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\MedicalRecordRequest;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    /**
     * Step form for a new record. It always starts from one of the doctor's appointments.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $appointment = $this->appointmentFrom($request, $request->integer('appointment'));

        if (! $appointment->canHaveMedicalRecord()) {
            return $this->notAllowed();
        }

        return view('doctor.records.create', ['appointment' => $appointment]);
    }

    public function store(MedicalRecordRequest $request, AppointmentService $appointments): RedirectResponse
    {
        $appointment = $this->appointmentFrom($request, $request->integer('appointment_id'));

        if (! $appointment->canHaveMedicalRecord()) {
            return $this->notAllowed();
        }

        $data = $request->validated();
        $stored = [];

        try {
            DB::transaction(function () use ($data, $request, $appointment, &$stored) {
                $record = MedicalRecord::create([
                    'patient_id' => $appointment->patient_id,
                    'doctor_id' => $request->user()->id,
                    'appointment_id' => $appointment->id,
                    'visit_date' => $data['visit_date'],
                    'chief_complaint' => $data['chief_complaint'],
                    'symptoms' => $data['symptoms'] ?? null,
                    'diagnosis' => $data['diagnosis'],
                    'treatment_plan' => $data['treatment_plan'] ?? null,
                    'prescription' => $data['prescription'] ?? null,
                    'condition' => $data['condition'],
                    'follow_up_date' => $data['follow_up_date'] ?? null,
                ]);

                foreach ($request->file('prescription_files', []) as $file) {
                    $stored[] = $this->storeFile($record, $file, MedicalRecordAttachment::TYPE_PRESCRIPTION);
                }
                foreach ($request->file('attachments', []) as $file) {
                    $stored[] = $this->storeFile($record, $file, MedicalRecordAttachment::TYPE_OTHER);
                }
            });
        } catch (\Throwable $e) {
            // The database rolled back, so remove any file that was already written.
            Storage::delete($stored);

            throw $e;
        }

        // Writing the record finishes the visit.
        if ($appointment->isBooked()) {
            $appointments->complete($appointment);
        }

        return to_route('doctor.appointments.index')->with('status', 'Medical record saved.');
    }

    /**
     * Find the appointment and make sure it is on one of this doctor's own slots.
     */
    private function appointmentFrom(Request $request, int $id): Appointment
    {
        $appointment = Appointment::with('patient', 'slot', 'service')->findOrFail($id);

        abort_unless($appointment->slot->doctor_id === $request->user()->id, 403);

        return $appointment;
    }

    private function notAllowed(): RedirectResponse
    {
        return to_route('doctor.appointments.index')
            ->with('error', 'A medical record can only be written once, after the visit has started.');
    }

    /**
     * Save one upload on the private disk and register it. Returns the stored path.
     */
    private function storeFile(MedicalRecord $record, UploadedFile $file, string $type): string
    {
        $path = $file->store("medical-records/{$record->id}");

        $record->attachments()->create([
            'type' => $type,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return $path;
    }
}
