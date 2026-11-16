<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RosterSlotRequest;
use App\Models\RosterSlot;
use App\Models\Service;
use App\Models\User;
use App\Support\Week;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RosterSlotController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'doctor' => ['nullable', 'integer'],
            'service' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(SlotStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $slots = RosterSlot::query()
            ->with('doctor.doctorProfile.service')
            ->withExists('activeAppointment')
            ->when($filters['search'] ?? null, fn ($query, $search) => $this->applySearch($query, trim($search)))
            ->when($filters['doctor'] ?? null, fn ($query, $doctor) => $query->where('doctor_id', $doctor))
            ->when($filters['service'] ?? null, function ($query, $service) {
                $query->whereHas('doctor.doctorProfile', fn ($q) => $q->where('service_id', $service));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('slot_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('slot_date', '<=', $to))
            ->orderByDesc('slot_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rosters.index', [
            'slots' => $slots,
            'doctors' => $this->doctors(),
            'services' => Service::orderBy('name')->get(),
            'filtered' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }

    /**
     * Match doctor name, notes or the slot ID (accepts "SLT-00012", "slt12" or just "12").
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('notes', 'like', "%{$search}%")
                ->orWhereHas('doctor', fn ($d) => $d->where('name', 'like', "%{$search}%"));

            if (preg_match('/^(?:slt-?)?0*(\d+)$/i', $search, $match)) {
                $q->orWhere('id', (int) $match[1]);
            }
        });
    }

    /**
     * Weekly calendar (Monday to Sunday). The week comes from ?week=YYYY-MM-DD, any day in that week.
     */
    public function calendar(Request $request): View
    {
        $weekStart = Week::start($request->query('week'));
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
