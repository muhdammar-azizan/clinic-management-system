<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DoctorRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        $doctors = User::query()
            ->where('role', Role::Doctor)
            ->with('doctorProfile.service')
            ->when($request->string('search')->trim()->toString(), function ($query, $search) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->integer('service'), function ($query, $serviceId) {
                $query->whereHas('doctorProfile', fn ($q) => $q->where('service_id', $serviceId));
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.doctors.index', [
            'doctors' => $doctors,
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.doctors.create', [
            'doctor' => new User,
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(DoctorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $doctor = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
            ]);
            // Role is not mass assignable on purpose, so set it explicitly.
            $doctor->role = Role::Doctor;
            $doctor->email_verified_at = now();
            $doctor->save();

            $doctor->doctorProfile()->create([
                'service_id' => $data['service_id'],
                'license_number' => $data['license_number'] ?? null,
                'bio' => $data['bio'] ?? null,
            ]);
        });

        return to_route('admin.doctors.index')->with('status', 'Doctor account created.');
    }

    public function edit(User $doctor): View
    {
        $this->ensureDoctor($doctor);

        return view('admin.doctors.edit', [
            'doctor' => $doctor->load('doctorProfile'),
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function update(DoctorRequest $request, User $doctor): RedirectResponse
    {
        $this->ensureDoctor($doctor);
        $data = $request->validated();

        DB::transaction(function () use ($data, $doctor) {
            $doctor->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);

            if (! empty($data['password'])) {
                $doctor->password = $data['password'];
            }

            $doctor->save();

            $doctor->doctorProfile()->updateOrCreate([], [
                'service_id' => $data['service_id'],
                'license_number' => $data['license_number'] ?? null,
                'bio' => $data['bio'] ?? null,
            ]);
        });

        return to_route('admin.doctors.index')->with('status', 'Doctor details updated.');
    }

    public function destroy(User $doctor): RedirectResponse
    {
        $this->ensureDoctor($doctor);

        if ($doctor->writtenRecords()->withTrashed()->exists()) {
            return to_route('admin.doctors.index')
                ->with('error', 'This doctor has medical records and cannot be deleted.');
        }

        $hasBookings = Appointment::where('status', AppointmentStatus::Booked)
            ->whereHas('slot', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->exists();

        if ($hasBookings) {
            return to_route('admin.doctors.index')
                ->with('error', 'This doctor has booked appointments. Cancel or complete them first.');
        }

        $doctor->delete();

        return to_route('admin.doctors.index')->with('status', 'Doctor account deleted.');
    }

    /**
     * Route model binding resolves any user, so make sure it is a doctor.
     */
    private function ensureDoctor(User $user): void
    {
        abort_unless($user->isDoctor(), 404);
    }
}
