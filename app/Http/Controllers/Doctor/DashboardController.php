<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $doctorId = $request->user()->id;

        // Every appointment query is limited to slots that belong to this doctor.
        $mine = fn () => Appointment::query()->whereHas('slot', fn ($q) => $q->where('doctor_id', $doctorId));

        return view('doctor.dashboard', [
            'stats' => [
                'today' => $mine()->onDay(today())->count(),
                'upcoming' => $mine()->upcoming()->count(),
                'awaiting' => $mine()->awaitingRecord()->count(),
                'records' => MedicalRecord::where('doctor_id', $doctorId)->count(),
            ],
            'todays' => $mine()->onDay(today())
                ->with('patient', 'slot', 'service')
                ->orderedBySlot('asc')
                ->get(),
            'awaitingRecord' => $mine()->awaitingRecord()
                ->with('patient', 'slot', 'service')
                ->orderedBySlot('desc')
                ->limit(5)
                ->get(),
        ]);
    }
}
