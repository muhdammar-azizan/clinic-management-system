<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateRosterRequest;
use App\Models\User;
use App\Services\RosterGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RosterGeneratorController extends Controller
{
    public function create(): View
    {
        return view('admin.rosters.generate', [
            'doctors' => User::where('role', Role::Doctor)->orderBy('name')->get(),
            'durations' => GenerateRosterRequest::DURATIONS,
            'weekdays' => [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'],
        ]);
    }

    public function store(GenerateRosterRequest $request, RosterGenerator $generator): RedirectResponse
    {
        $data = $request->validated();

        if ($generator->candidates($data)->count() > RosterGenerator::MAX_SLOTS) {
            return back()->withInput()->withErrors([
                'duration' => 'That pattern would create more than '.RosterGenerator::MAX_SLOTS.' slots. Use a shorter date range or longer sessions.',
            ]);
        }

        $doctor = User::where('role', Role::Doctor)->findOrFail($data['doctor_id']);
        $result = $generator->generate($doctor, $data);

        if ($result['created'] === 0) {
            return back()->withInput()->with('error', $this->summary($result, 'No slots were created.'));
        }

        return to_route('admin.rosters.index')
            ->with('status', $this->summary($result, "{$result['created']} slots created for {$doctor->name}."));
    }

    /**
     * @param  array{created: int, skipped_past: int, skipped_existing: int}  $result
     */
    private function summary(array $result, string $lead): string
    {
        $skipped = [];

        if ($result['skipped_past'] > 0) {
            $skipped[] = "{$result['skipped_past']} already in the past";
        }
        if ($result['skipped_existing'] > 0) {
            $skipped[] = "{$result['skipped_existing']} overlapping an existing slot";
        }

        return $skipped === [] ? $lead : $lead.' Skipped: '.implode(', ', $skipped).'.';
    }
}
