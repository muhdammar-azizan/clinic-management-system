<?php

namespace Tests\Quality\Module4;

use App\Models\MedicalRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\Quality\QualityTestCase;

/**
 * Module 4 (Fatin's report), Performance.
 *
 * These checks run inside PHPUnit, so they measure the application and the database, not a real web
 * server. The report's ApacheBench run (ab -n 100 -c 10) is repeated separately against a running server.
 */
class PerformanceTest extends QualityTestCase
{
    /** The report's target: a page within 2 seconds. */
    private const TARGET_MS = 2000;

    private User $doctor;

    private User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = $this->doctor();
        $this->patient = $this->patient();
    }

    private function records(int $count): void
    {
        MedicalRecord::factory()->count($count)->create(['doctor_id' => $this->doctor->id, 'patient_id' => $this->patient->id]);
    }

    /**
     * @return array{int, float} number of queries and milliseconds for one request
     */
    private function measure(User $user, string $url): array
    {
        $this->actingAs($user);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $start = hrtime(true);
        $this->get($url)->assertOk();
        $milliseconds = (hrtime(true) - $start) / 1e6;

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$queries, $milliseconds];
    }

    #[Group('M4-PERF-1')]
    public function test_the_columns_the_record_pages_filter_and_sort_by_are_indexed(): void
    {
        $indexed = collect(Schema::getIndexes('medical_records'))->pluck('columns');

        // The doctor's list filters by doctor_id and sorts by visit_date. The patient's history is the same for patient_id.
        $this->assertTrue($indexed->contains(['doctor_id', 'visit_date']), 'doctor_id and visit_date need an index.');
        $this->assertTrue($indexed->contains(['patient_id', 'visit_date']), 'patient_id and visit_date need an index.');
    }

    #[Group('M4-PERF-1')]
    public function test_the_number_of_queries_does_not_grow_with_the_number_of_records(): void
    {
        $this->records(5);
        [$few] = $this->measure($this->doctor, '/doctor/records');

        $this->records(60);
        [$many] = $this->measure($this->doctor, '/doctor/records');

        // A page shows at most 15 rows, so extra records must not add queries (no N+1).
        $this->assertSame($few, $many);
        $this->assertLessThanOrEqual(8, $many);
    }

    #[Group('M4-PERF-1')]
    public function test_the_patient_history_page_does_not_add_queries_per_record(): void
    {
        $this->records(3);
        [$few] = $this->measure($this->patient, '/patient/records');

        $this->records(40);
        [$many] = $this->measure($this->patient, '/patient/records');

        $this->assertSame($few, $many);
    }

    #[Group('M4-PERF-2')]
    public function test_a_doctor_opens_a_busy_record_list_well_within_two_seconds(): void
    {
        $this->records(200);
        $this->measure($this->doctor, '/doctor/records'); // warm up

        $times = [];
        for ($i = 0; $i < 20; $i++) {
            [, $times[]] = $this->measure($this->doctor, '/doctor/records');
        }

        $this->assertLessThan(self::TARGET_MS, max($times), 'Slowest request: '.round(max($times)).' ms');
        $this->assertLessThan(self::TARGET_MS, array_sum($times) / count($times));
    }

    #[Group('M4-PERF-2')]
    public function test_opening_one_record_with_a_search_stays_fast_with_many_records(): void
    {
        $this->records(200);
        $record = MedicalRecord::first();

        [, $show] = $this->measure($this->doctor, "/doctor/records/{$record->id}");
        [, $search] = $this->measure($this->doctor, '/doctor/records?search='.urlencode($record->diagnosis));

        $this->assertLessThan(self::TARGET_MS, $show);
        $this->assertLessThan(self::TARGET_MS, $search);
    }
}
