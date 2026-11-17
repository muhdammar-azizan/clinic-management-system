<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'patients' => User::where('role', Role::Patient)->count(),
                'doctors' => User::where('role', Role::Doctor)->count(),
                'today' => Appointment::onDay(today())->count(),
                'upcoming' => Appointment::upcoming()->count(),
                'openSlots' => RosterSlot::bookable()->count(),
                // Only the number of records. Clinical content is never shown to the admin.
                'records' => MedicalRecord::count(),
            ],
            'nextAppointments' => Appointment::upcoming()
                ->with('patient', 'slot.doctor', 'service')
                ->orderedBySlot('asc')
                ->limit(5)
                ->get(),
        ]);
    }
}
