<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RosterSlotRequest;
use App\Models\RosterSlot;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RosterSlotController extends Controller
{
    public function index(): View
    {
        $slots = RosterSlot::query()
            ->with('doctor.doctorProfile.service')
            ->withExists('activeAppointment')
            ->orderByDesc('slot_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rosters.index', ['slots' => $slots]);
    }

    /**
     * Weekly calendar (Monday to Sunday). The week comes from ?week=YYYY-MM-DD, any day in that week.
     */
    public function calendar(Request $request): View
    {
        try {
            $anchor = $request->filled('week') ? Carbon::createFromFormat('Y-m-d', $request->string('week')->toString())->startOfDay() : today();
        } catch (InvalidFormatException) {
            $anchor = today();
        }

        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);
        $doctorId = $request->integer('doctor') ?: null;

        $slotsByDay = RosterSlot::query()
            ->with('doctor')
            ->withExists('activeAppointment')
            ->whereBetween('slot_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (RosterSlot $slot) => $slot->slot_date->toDateString());

        return view('admin.rosters.calendar', [
            'weekStart' => $weekStart,
            'days' => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i)),
            'slotsByDay' => $slotsByDay,
            'doctors' => $this->doctors(),
            'doctorId' => $doctorId,
        ]);
    }

    public function create(): View
    {
        return view('admin.rosters.create', [
            'slot' => new RosterSlot(['slot_date' => today()]),
            'doctors' => $this->doctors(),
        ]);
    }

    public function store(RosterSlotRequest $request): RedirectResponse
    {
        RosterSlot::create($request->validated());

        return to_route('admin.rosters.index')->with('status', 'Slot created.');
    }

    public function edit(RosterSlot $slot): View|RedirectResponse
    {
        if ($slot->hasActiveBooking()) {
            return $this->lockedResponse();
        }

        return view('admin.rosters.edit', [
            'slot' => $slot,
            'doctors' => $this->doctors(),
        ]);
    }

    public function update(RosterSlotRequest $request, RosterSlot $slot): RedirectResponse
    {
        if ($slot->hasActiveBooking()) {
            return $this->lockedResponse();
        }

        $slot->update($request->validated());

        return to_route('admin.rosters.index')->with('status', 'Slot updated.');
    }

    public function destroy(RosterSlot $slot): RedirectResponse
    {
        if ($slot->hasActiveBooking()) {
            return $this->lockedResponse();
        }

        $slot->delete();

        return to_route('admin.rosters.index')->with('status', 'Slot deleted.');
    }

    private function doctors()
    {
        return User::where('role', Role::Doctor)->orderBy('name')->get();
    }

    private function lockedResponse(): RedirectResponse
    {
        return to_route('admin.rosters.index')
            ->with('error', 'This slot has a booked appointment. Cancel the appointment first.');
    }
}
