<?php

namespace App\Http\Controllers\Doctor;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
        ]);

        $appointments = Appointment::query()
            ->whereHas('slot', fn ($q) => $q->where('doctor_id', $request->user()->id))
            ->with('patient', 'slot', 'service')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('patient', fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('appointments.status', $status))
            ->orderedBySlot()
            ->paginate(15)
            ->withQueryString();

        return view('doctor.appointments.index', [
            'appointments' => $appointments,
            'filtered' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }

    public function edit(Request $request, Appointment $appointment): View
    {
        $this->authorize('manage', $appointment);

        return view('doctor.appointments.edit', [
            'appointment' => $appointment->load('patient', 'slot', 'service'),
        ]);
    }

    public function update(Request $request, Appointment $appointment, AppointmentService $appointments): RedirectResponse
    {
        $this->authorize('manage', $appointment);

        $data = $request->validate([
            'status' => ['required', Rule::in([AppointmentStatus::Completed->value, AppointmentStatus::Cancelled->value])],
        ]);

        $error = $data['status'] === AppointmentStatus::Completed->value
            ? $appointments->complete($appointment)
            : $appointments->cancel($appointment);

        if ($error) {
            return to_route('doctor.appointments.edit', $appointment)->with('error', $error);
        }

        return to_route('doctor.appointments.index')->with('status', 'Appointment updated.');
    }

    public function destroy(Request $request, Appointment $appointment, AppointmentService $appointments): RedirectResponse
    {
        $this->authorize('manage', $appointment);

        $appointments->delete($appointment);

        return to_route('doctor.appointments.index')->with('status', 'Appointment deleted.');
    }
}
