<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\RosterSlot;
use App\Support\Week;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only weekly schedule for the signed-in doctor. Only the admin changes the roster.
 */
class ScheduleController extends Controller
{
    public function __invoke(Request $request): View
    {
        $weekStart = Week::start($request->query('week'));

        $slots = RosterSlot::query()
            ->where('doctor_id', $request->user()->id)
            ->whereBetween('slot_date', [$weekStart->toDateString(), $weekStart->copy()->addDays(6)->toDateString()])
            ->with('activeAppointment.patient', 'activeAppointment.service')
            ->orderBy('start_time')
            ->get();

        return view('doctor.schedule', [
            'weekStart' => $weekStart,
            'days' => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i)),
            'slotsByDay' => $slots->groupBy(fn (RosterSlot $slot) => $slot->slot_date->toDateString()),
            'totals' => [
                'slots' => $slots->count(),
                'available' => $slots->filter->isAvailable()->count(),
                'booked' => $slots->filter(fn ($slot) => $slot->activeAppointment !== null)->count(),
            ],
        ]);
    }
}
