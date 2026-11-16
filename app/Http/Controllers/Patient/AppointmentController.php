<?php

namespace App\Http\Controllers\Patient;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->patient_id === $request->user()->id, 403);

        $error = DB::transaction(function () use ($appointment) {
            $appointment = Appointment::with('slot')->lockForUpdate()->findOrFail($appointment->id);

            if (! $appointment->canBeCancelled()) {
                return 'Only upcoming booked appointments can be cancelled.';
            }

            $appointment->update(['status' => AppointmentStatus::Cancelled]);
            // Give the slot back so another patient can book it.
            $appointment->slot->update(['status' => SlotStatus::Available]);

            return null;
        });

        return to_route('patient.appointments.index')
            ->with($error ? 'error' : 'status', $error ?? 'Your appointment has been cancelled.');
    }
}
