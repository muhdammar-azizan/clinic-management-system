<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $patient = $request->user();

        $upcoming = Appointment::upcoming()
            ->where('patient_id', $patient->id)
            ->with('slot.doctor', 'service')
            ->orderedBySlot('asc')
            ->get();

        return view('patient.dashboard', [
            'upcoming' => $upcoming->take(3),
            'upcomingCount' => $upcoming->count(),
            'next' => $upcoming->first(),
            'recordsCount' => $patient->medicalRecords()->count(),
            'latestRecord' => $patient->medicalRecords()->with('doctor')->orderByDesc('visit_date')->orderByDesc('id')->first(),
        ]);
    }
}
