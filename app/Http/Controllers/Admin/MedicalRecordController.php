<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin can see that records exist, who wrote them and when, but never the clinical content.
 * Diagnosis, treatment, prescription and files are not loaded, shown or searchable here.
 */
class MedicalRecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'doctor' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $records = MedicalRecord::query()
            ->select(['id', 'patient_id', 'doctor_id', 'appointment_id', 'visit_date', 'created_at'])
            ->with('patient:id,name,email', 'doctor:id,name', 'appointment:id,service_id', 'appointment.service:id,name')
            ->withCount('attachments')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $search = trim($search);

                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('doctor', fn ($d) => $d->where('name', 'like', "%{$search}%"));

                    // Accept "MR-00012", "mr12" or just "12".
                    if (preg_match('/^(?:mr-?)?0*(\d+)$/i', $search, $match)) {
                        $q->orWhere('id', (int) $match[1]);
                    }
                });
            })
            ->when($filters['doctor'] ?? null, fn ($query, $doctor) => $query->where('doctor_id', $doctor))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('visit_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('visit_date', '<=', $to))
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.records.index', [
            'records' => $records,
            'doctors' => User::where('role', Role::Doctor)->orderBy('name')->get(),
            'filtered' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }
}
