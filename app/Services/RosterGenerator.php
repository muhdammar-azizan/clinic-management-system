<?php

namespace App\Services;

use App\Models\RosterSlot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds many roster slots at once from a working pattern, for example
 * "Monday to Friday, 09:00 to 17:00, 30 minute sessions, lunch 13:00 to 14:00".
 */
class RosterGenerator
{
    /** The longest date range that can be generated in one go. */
    public const MAX_DAYS = 62;

    /** Safety cap so one request cannot create thousands of rows. */
    public const MAX_SLOTS = 500;

    /**
     * Every slot the pattern describes, without looking at the database.
     *
     * @param  array{from: string, to: string, days: list<int|string>, work_start: string, work_end: string, duration: int|string, break_start?: ?string, break_end?: ?string}  $pattern
     * @return Collection<int, array{date: string, start: string, end: string}>
     */
    public function candidates(array $pattern): Collection
    {
        $days = array_map('intval', $pattern['days']);
        $duration = (int) $pattern['duration'];
        $breakStart = $pattern['break_start'] ?? null;
        $breakEnd = $pattern['break_end'] ?? null;
        $slots = collect();

        $last = Carbon::parse($pattern['to'])->startOfDay();

        for ($day = Carbon::parse($pattern['from'])->startOfDay(); $day->lte($last); $day = $day->copy()->addDay()) {
            if (! in_array($day->dayOfWeekIso, $days, true)) {
                continue;
            }

            $workEnd = $day->copy()->setTimeFromTimeString($pattern['work_end']);
            $cursor = $day->copy()->setTimeFromTimeString($pattern['work_start']);

            while ($cursor->copy()->addMinutes($duration)->lte($workEnd)) {
                $start = $cursor->copy();
                $end = $cursor->copy()->addMinutes($duration);
                $cursor = $end->copy();

                // Leave out sessions that touch the break.
                if ($breakStart && $breakEnd && $start->format('H:i') < $breakEnd && $end->format('H:i') > $breakStart) {
                    continue;
                }

                $slots->push([
                    'date' => $day->toDateString(),
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                ]);
            }
        }

        return $slots;
    }

    /**
     * Create the slots for a doctor. Sessions in the past, or that overlap a slot the
     * doctor already has, are skipped and counted instead of failing the whole run.
     *
     * @param  array<string, mixed>  $pattern  same shape as candidates(), plus an optional "notes"
     * @return array{created: int, skipped_past: int, skipped_existing: int}
     */
    public function generate(User $doctor, array $pattern): array
    {
        $candidates = $this->candidates($pattern);
        $result = ['created' => 0, 'skipped_past' => 0, 'skipped_existing' => 0];

        $existing = RosterSlot::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('slot_date', [$pattern['from'], $pattern['to']])
            ->get(['slot_date', 'start_time', 'end_time'])
            ->groupBy(fn (RosterSlot $slot) => $slot->slot_date->toDateString());

        $today = today()->toDateString();
        $now = now()->format('H:i');

        DB::transaction(function () use ($candidates, $existing, $doctor, $pattern, $today, $now, &$result) {
            foreach ($candidates as $candidate) {
                $isPast = $candidate['date'] < $today || ($candidate['date'] === $today && $candidate['start'] <= $now);

                if ($isPast) {
                    $result['skipped_past']++;

                    continue;
                }

                $clash = $existing->get($candidate['date'], collect())->contains(
                    fn (RosterSlot $slot) => $this->overlaps($candidate['start'], $candidate['end'], $slot)
                );

                if ($clash) {
                    $result['skipped_existing']++;

                    continue;
                }

                RosterSlot::create([
                    'doctor_id' => $doctor->id,
                    'slot_date' => $candidate['date'],
                    'start_time' => $candidate['start'],
                    'end_time' => $candidate['end'],
                    'notes' => $pattern['notes'] ?? null,
                ]);
                $result['created']++;
            }
        });

        return $result;
    }

    /**
     * Same rule as the slot form: an old slot without an end time counts as an instant.
     */
    private function overlaps(string $start, string $end, RosterSlot $slot): bool
    {
        $slotStart = $slot->start_time_label;
        $slotEnd = $slot->end_time_label;

        if ($slotEnd === null) {
            return $slotStart >= $start && $slotStart < $end;
        }

        return $slotStart < $end && $slotEnd > $start;
    }
}
