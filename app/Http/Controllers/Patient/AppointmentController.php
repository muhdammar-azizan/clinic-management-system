<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $appointments = $request->user()->appointments()
            ->with('slot.doctor', 'service')
            ->get()
            ->sortBy(fn (Appointment $a) => $a->slot->startsAt()->timestamp);

        // Upcoming: still booked and not started yet. Everything else is history.
        [$upcoming, $history] = $appointments->partition(fn (Appointment $a) => $a->canBeCancelled());

        return view('patient.appointments.index', [
            'upcoming' => $upcoming->values(),
            'history' => $history->sortByDesc(fn (Appointment $a) => $a->slot->startsAt()->timestamp)->values(),
        ]);
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentService $appointments): RedirectResponse
    {
        abort_unless($appointment->patient_id === $request->user()->id, 403);

        $error = $appointments->cancel($appointment, onlyBeforeStart: true);

        return to_route('patient.appointments.index')
            ->with($error ? 'error' : 'status', $error ?? 'Your appointment has been cancelled.');
    }
}
