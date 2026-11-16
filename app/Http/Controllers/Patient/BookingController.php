<?php

namespace App\Http\Controllers\Patient;

use App\Enums\AppointmentStatus;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Models\Appointment;
use App\Models\RosterSlot;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    /** How many days ahead a patient can book. */
    private const BOOKING_WINDOW_DAYS = 30;

    public function create(Request $request): View
    {
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $service = $services->firstWhere('id', $request->integer('service'));

        $slotsByDay = collect();

        if ($service) {
            $slotsByDay = RosterSlot::query()
                ->bookable()
                ->whereDate('slot_date', '<=', today()->addDays(self::BOOKING_WINDOW_DAYS))
                ->whereHas('doctor.doctorProfile', fn ($q) => $q->where('service_id', $service->id))
                ->with('doctor')
                ->orderBy('slot_date')
                ->orderBy('start_time')
                ->get()
                ->groupBy(fn (RosterSlot $slot) => $slot->slot_date->toDateString());
        }

        return view('patient.booking.create', [
            'services' => $services,
            'service' => $service,
            'slotsByDay' => $slotsByDay,
        ]);
    }

    public function store(BookAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = $request->user();

        try {
            $error = DB::transaction(function () use ($data, $patient) {
                // Lock the slot row so two patients cannot confirm the same slot at once.
                $slot = RosterSlot::query()->lockForUpdate()->with('doctor.doctorProfile')->findOrFail($data['roster_slot_id']);

                if ($slot->doctor->doctorProfile?->service_id !== (int) $data['service_id']) {
                    return 'That slot does not belong to the selected service.';
                }

                if (! RosterSlot::bookable()->whereKey($slot->id)->exists()) {
                    return 'Sorry, that slot is no longer available. Please choose another.';
                }

                $clash = Appointment::query()
                    ->where('patient_id', $patient->id)
                    ->where('status', AppointmentStatus::Booked)
                    ->whereHas('slot', fn ($q) => $q
                        ->whereDate('slot_date', $slot->slot_date)
                        ->where('start_time', $slot->start_time))
                    ->exists();

                if ($clash) {
                    return 'You already have an appointment at that date and time.';
                }

                Appointment::create([
                    'patient_id' => $patient->id,
                    'roster_slot_id' => $slot->id,
                    'service_id' => $data['service_id'],
                    'reason' => $data['reason'] ?? null,
                ]);

                $slot->update(['status' => SlotStatus::Occupied]);

                return null;
            });
        } catch (QueryException) {
            // The unique index on active_slot_id caught a simultaneous booking.
            $error = 'Sorry, that slot was just taken. Please choose another.';
        }

        if ($error) {
            return to_route('patient.booking.create', ['service' => $data['service_id']])
                ->withInput()
                ->with('error', $error);
        }

        return to_route('patient.appointments.index')->with('status', 'Your appointment has been booked.');
    }
}
