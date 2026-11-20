<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\ConditionStatus;
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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicalRecordController extends Controller
{
    /** Fields saved from the form, shared by store and update. */
    private const RECORD_FIELDS = [
        'visit_date', 'chief_complaint', 'symptoms', 'diagnosis',
        'treatment_plan', 'prescription', 'condition', 'follow_up_date',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', Rule::enum(ConditionStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $records = MedicalRecord::query()
            ->where('doctor_id', $request->user()->id)
            ->with('patient')
            ->withCount('attachments')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $search = trim($search);

                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhere('diagnosis', 'like', "%{$search}%");

                    // Accept "MR-00012", "mr12" or just "12".
                    if (preg_match('/^(?:mr-?)?0*(\d+)$/i', $search, $match)) {
                        $q->orWhere('id', (int) $match[1]);
                    }
                });
            })
            ->when($filters['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('visit_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('visit_date', '<=', $to))
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('doctor.records.index', [
            'records' => $records,
            'filtered' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }

    /**
     * Step form for a new record. It always starts from one of the doctor's appointments.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $appointment = $this->appointmentFrom($request, $request->integer('appointment'));

        if (! $appointment->canHaveMedicalRecord()) {
            return $this->notAllowed();
        }

        return view('doctor.records.create', ['appointment' => $appointment, 'record' => null]);
    }

    public function store(MedicalRecordRequest $request, AppointmentService $appointments): RedirectResponse
    {
        $appointment = $this->appointmentFrom($request, $request->integer('appointment_id'));

        if (! $appointment->canHaveMedicalRecord()) {
            return $this->notAllowed();
        }

        $this->saveWithFiles($request, function () use ($request, $appointment) {
            $record = MedicalRecord::create($request->safe()->only(self::RECORD_FIELDS) + [
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $request->user()->id,
                'appointment_id' => $appointment->id,
            ]);

            return [$record, []];
        });

        // Writing the record finishes the visit.
        if ($appointment->isBooked()) {
            $appointments->complete($appointment);
        }

        return to_route('doctor.appointments.index')->with('status', 'Medical record saved.');
    }

    public function show(Request $request, MedicalRecord $record): View
    {
        $this->authorize('manage', $record);

        return view('doctor.records.show', [
            'record' => $record->load('patient', 'attachments', 'appointment'),
        ]);
    }

    public function edit(Request $request, MedicalRecord $record): View
    {
        $this->authorize('manage', $record);

        $record->load('patient', 'attachments', 'appointment.slot', 'appointment.service');

        return view('doctor.records.edit', [
            'record' => $record,
            'appointment' => $record->appointment,
        ]);
    }

    public function update(MedicalRecordRequest $request, MedicalRecord $record): RedirectResponse
    {
        $this->authorize('manage', $record);

        $removeIds = collect($request->input('remove_attachments', []))->map(fn ($id) => (int) $id)->all();

        $this->saveWithFiles($request, function () use ($request, $record, $removeIds) {
            $record->update($request->safe()->only(self::RECORD_FIELDS));

            // Only attachments that belong to this record can be removed.
            $removed = $record->attachments()->whereIn('id', $removeIds)->get();
            $record->attachments()->whereIn('id', $removeIds)->delete();

            return [$record, $removed->pluck('path')->all()];
        });

        return to_route('doctor.records.show', $record)->with('status', 'Medical record updated.');
    }

    public function destroy(Request $request, MedicalRecord $record): RedirectResponse
    {
        $this->authorize('manage', $record);

        // Soft delete: the row and its files stay in storage and can be restored by a developer.
        $record->delete();

        return to_route('doctor.records.index')->with('status', 'Medical record deleted.');
    }

    /**
     * Run the database part inside a transaction, then store uploads for the record it returns.
     * The callback returns [record, pathsToDeleteAfterCommit].
     */
    private function saveWithFiles(MedicalRecordRequest $request, \Closure $persist): void
    {
        $stored = [];

        try {
            $obsolete = DB::transaction(function () use ($request, $persist, &$stored) {
                [$record, $obsolete] = $persist();

                foreach ($request->file('prescription_files', []) as $file) {
                    $this->storeFile($record, $file, MedicalRecordAttachment::TYPE_PRESCRIPTION, $stored);
                }
                foreach ($request->file('attachments', []) as $file) {
                    $this->storeFile($record, $file, MedicalRecordAttachment::TYPE_OTHER, $stored);
                }

                return $obsolete;
            });
        } catch (\Throwable $e) {
            // The database rolled back, so remove any file that was already written.
            Storage::delete($stored);

            throw $e;
        }

        // Files of removed attachments are deleted only after the commit succeeded.
        Storage::delete($obsolete);
    }

    /**
     * Find the appointment and make sure it is on one of this doctor's own slots.
     */
    private function appointmentFrom(Request $request, int $id): Appointment
    {
        $appointment = Appointment::with('patient', 'slot', 'service')->findOrFail($id);

        $this->authorize('writeRecord', $appointment);

        return $appointment;
    }

    private function notAllowed(): RedirectResponse
    {
        return to_route('doctor.appointments.index')
            ->with('error', 'A medical record can only be written once, after the visit has started.');
    }

    /**
     * Save one upload on the private disk and register it. The path is added to $stored before the
     * database is touched, so a failure afterwards still lets the caller delete the file.
     *
     * @param  list<string>  $stored
     */
    private function storeFile(MedicalRecord $record, UploadedFile $file, string $type, array &$stored): void
    {
        $path = $file->store("medical-records/{$record->id}");
        $stored[] = $path;

        $record->attachments()->create([
            'type' => $type,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
