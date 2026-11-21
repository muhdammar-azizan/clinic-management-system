<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PatientRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $patients = User::query()
            ->where('role', Role::Patient)
            ->withCount('appointments')
            ->when($request->string('search')->trim()->toString(), function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->whereLike('name', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%")
                    ->orWhereLike('phone', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.patients.index', ['patients' => $patients]);
    }

    public function show(User $patient): View
    {
        $this->ensurePatient($patient);

        // Admin sees visit history but never diagnosis details, so only a record count is loaded.
        $patient->loadCount('medicalRecords');
        $patient->load('appointments.slot.doctor', 'appointments.service');

        return view('admin.patients.show', [
            'patient' => $patient,
            'appointments' => $patient->appointments
                ->sortByDesc(fn ($a) => $a->slot->slot_date->toDateString().$a->slot->start_time)
                ->values(),
        ]);
    }

    public function edit(User $patient): View
    {
        $this->ensurePatient($patient);

        return view('admin.patients.edit', ['patient' => $patient]);
    }

    public function update(PatientRequest $request, User $patient): RedirectResponse
    {
        $this->ensurePatient($patient);

        $patient->fill($request->validated());

        if ($patient->isDirty('email')) {
            $patient->email_verified_at = null;
        }

        $patient->save();

        return to_route('admin.patients.show', $patient)->with('status', 'Patient details updated.');
    }

    public function destroy(User $patient): RedirectResponse
    {
        $this->ensurePatient($patient);

        // Appointments and medical records are removed with the account.
        $patient->delete();

        return to_route('admin.patients.index')->with('status', 'Patient record deleted.');
    }

    /**
     * Route model binding resolves any user, so make sure it is a patient.
     */
    private function ensurePatient(User $user): void
    {
        abort_unless($user->isPatient(), 404);
    }
}
