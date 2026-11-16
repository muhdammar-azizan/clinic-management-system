<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RosterSlotRequest;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
