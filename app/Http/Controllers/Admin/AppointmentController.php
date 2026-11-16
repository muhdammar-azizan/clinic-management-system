<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin can see every appointment but not change it. Doctors and patients manage their own.
 */
class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'doctor' => ['nullable', 'integer'],
            'service' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $appointments = Appointment::query()
            ->with('patient', 'slot.doctor', 'service')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $search = trim($search);

                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));

                    // Accept "APT-00012", "apt12" or just "12".
                    if (preg_match('/^(?:apt-?)?0*(\d+)$/i', $search, $match)) {
                        $q->orWhere('appointments.id', (int) $match[1]);
                    }
                });
            })
            ->when($filters['doctor'] ?? null, fn ($query, $doctor) => $query->whereHas('slot', fn ($q) => $q->where('doctor_id', $doctor)))
            ->when($filters['service'] ?? null, fn ($query, $service) => $query->where('service_id', $service))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('appointments.status', $status))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereHas('slot', fn ($q) => $q->whereDate('slot_date', '>=', $from)))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereHas('slot', fn ($q) => $q->whereDate('slot_date', '<=', $to)))
            ->orderedBySlot()
            ->paginate(15)
            ->withQueryString();

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'doctors' => User::where('role', Role::Doctor)->orderBy('name')->get(),
            'services' => Service::orderBy('name')->get(),
            'filtered' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }
}
