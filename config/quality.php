<?php

/*
|--------------------------------------------------------------------------
| Quality report settings
|--------------------------------------------------------------------------
|
| Everything the Quality page needs to turn raw test results into the metrics
| of the SQA reports (Module 3 by Ammar, Module 4 by Fatin). Results are
| written by "php artisan quality:report" and "php artisan quality:load-test"
| into the quality folder and are committed, so the page works after deploy.
|
*/

return [
    'path' => base_path('quality'),

    // Module 3 test cases from the report: id => [attribute, title].
    'test_cases' => [
        'TC-001' => ['Usability', 'Admin creates a new schedule slot'],
        'TC-002' => ['Usability', 'Admin views slots in list view and calendar view'],
        'TC-003' => ['Usability', 'Admin edits an existing slot'],
        'TC-004' => ['Usability', 'Admin searches and sorts the schedule list'],
        'TC-005' => ['Accuracy', 'System blocks a slot with a past date'],
        'TC-006' => ['Accuracy', 'System blocks a slot with a past time today'],
        'TC-007' => ['Accuracy', 'System accepts a valid future slot'],
        'TC-008' => ['Data Integrity', 'System prevents a duplicate slot'],
        'TC-009' => ['Data Integrity', 'Admin deletes a slot after confirmation'],
        'TC-010' => ['Data Integrity', 'Same doctor and date with a different time is allowed'],
        'TC-011' => ['Security', 'Doctor and patient are denied the roster pages'],
        'TC-012' => ['Security', 'Admin has full create, edit and delete access'],
        'TC-013' => ['Security', 'Doctor can only view their own schedule'],
    ],

    // Module 3 requirements (SRS in the report): id => meaning.
    'requirements' => [
        'SRS-REQ-301' => 'Admin creates a slot (doctor, date, start and end time, status)',
        'SRS-REQ-302' => 'Admin views a list of all slots',
        'SRS-REQ-303' => 'Slot details and availability status are shown',
        'SRS-REQ-304' => 'Admin edits a slot',
        'SRS-REQ-305' => 'Admin deletes a slot',
        'SRS-REQ-306' => 'List view and calendar view',
        'SRS-REQ-307' => 'No two slots of one doctor overlap',
        'SRS-REQ-308' => 'Search, filter and sort',
        'SRS-REQ-309' => 'Expired slots are never offered for booking',
        'SRS-REQ-310' => 'Past slots are rejected, even when added by the admin',
        'SRS-REQ-311' => 'Only the admin manages the roster, doctors view their own',
        'SRS-REQ-312' => 'Role-based access control',
    ],

    // Module 4 checks from Fatin's report: group => [attribute, title].
    'checks' => [
        'M4-PERF-1' => ['Performance', 'Indexes exist and the query count does not grow with the data'],
        'M4-PERF-2' => ['Performance', 'Pages answer well within 2 seconds'],
        'M4-REL-1' => ['Reliability', 'Foreign keys refuse links to people who do not exist'],
        'M4-REL-2' => ['Reliability', 'Delete needs confirmation and loses no data'],
        'M4-REL-3' => ['Reliability', 'A failed save rolls back and leaves no orphan file'],
        'M4-REL-4' => ['Reliability', '100 deletes and 100 reads without a failure'],
        'M4-USE-1' => ['Usability', 'Clear labels, required fields marked, form prefilled'],
        'M4-USE-2' => ['Usability', 'Specific error messages and nothing typed is lost'],
        'M4-USE-3' => ['Usability', 'Clear success message after saving'],
        'M4-USE-4' => ['Usability', 'Data Accurate Rate of 100%'],
    ],

    /*
     | Metrics. kind says how the value is worked out:
     |   pass_rate  = passed tests / tests in the groups x 100
     |   fail_rate  = failed tests / tests in the groups x 100
     |   feedback   = average portal feedback score, live from the database
     |   load_mean_ms, load_failed = from the ApacheBench run
     | target is [operator, number].
     */
    'metrics' => [
        ['module' => 3, 'attribute' => 'Usability', 'name' => 'Task Completion Rate', 'kind' => 'pass_rate', 'groups' => ['TC-001', 'TC-002', 'TC-003', 'TC-004'], 'target' => ['>', 90]],
        ['module' => 3, 'attribute' => 'Usability', 'name' => 'User Feedback Score', 'kind' => 'feedback', 'target' => ['>=', 2.5]],
        ['module' => 3, 'attribute' => 'Accuracy', 'name' => 'Accuracy Rate', 'kind' => 'pass_rate', 'groups' => ['TC-005', 'TC-006', 'TC-007'], 'target' => ['=', 100]],
        ['module' => 3, 'attribute' => 'Accuracy', 'name' => 'Expired Slot Error Rate', 'kind' => 'fail_rate', 'groups' => ['TC-005', 'TC-006'], 'target' => ['=', 0]],
        ['module' => 3, 'attribute' => 'Data Integrity', 'name' => 'Duplicate Conflict Rate', 'kind' => 'fail_rate', 'groups' => ['TC-008', 'TC-010'], 'target' => ['<', 5]],
        ['module' => 3, 'attribute' => 'Data Integrity', 'name' => 'Data Consistency Rate', 'kind' => 'pass_rate', 'groups' => ['TC-008', 'TC-009', 'TC-010'], 'target' => ['=', 100]],
        ['module' => 3, 'attribute' => 'Security', 'name' => 'Unauthorized Access Prevention Rate', 'kind' => 'pass_rate', 'groups' => ['TC-011', 'TC-013'], 'target' => ['>', 95]],
        ['module' => 3, 'attribute' => 'Security', 'name' => 'Permission Error Rate', 'kind' => 'fail_rate', 'groups' => ['TC-011', 'TC-012', 'TC-013'], 'target' => ['<', 5]],

        ['module' => 4, 'attribute' => 'Performance', 'name' => 'Performance checks passed', 'kind' => 'pass_rate', 'groups' => ['M4-PERF-1', 'M4-PERF-2'], 'target' => ['=', 100]],
        ['module' => 4, 'attribute' => 'Performance', 'name' => 'Slowest mean response under load', 'kind' => 'load_mean_ms', 'target' => ['<', 2000]],
        ['module' => 4, 'attribute' => 'Reliability', 'name' => 'Reliability checks passed', 'kind' => 'pass_rate', 'groups' => ['M4-REL-1', 'M4-REL-2', 'M4-REL-3', 'M4-REL-4'], 'target' => ['=', 100]],
        ['module' => 4, 'attribute' => 'Reliability', 'name' => 'Failed requests under load', 'kind' => 'load_failed', 'target' => ['=', 0]],
        ['module' => 4, 'attribute' => 'Usability', 'name' => 'Usability checks passed', 'kind' => 'pass_rate', 'groups' => ['M4-USE-1', 'M4-USE-2', 'M4-USE-3'], 'target' => ['=', 100]],
        ['module' => 4, 'attribute' => 'Usability', 'name' => 'Data Accurate Rate', 'kind' => 'pass_rate', 'groups' => ['M4-USE-4'], 'target' => ['=', 100]],
    ],

    // Load test pages. Each is opened with ApacheBench while signed in as the given account.
    'load_scenarios' => [
        ['label' => 'Doctor: medical record list', 'email' => 'doctor@clinic.test', 'path' => '/doctor/records'],
        ['label' => 'Doctor: weekly schedule', 'email' => 'doctor@clinic.test', 'path' => '/doctor/schedule'],
        ['label' => 'Patient: medical record list', 'email' => 'patient@clinic.test', 'path' => '/patient/records'],
        ['label' => 'Admin: roster list', 'email' => 'admin@clinic.test', 'path' => '/admin/rosters'],
        ['label' => 'Admin: appointments', 'email' => 'admin@clinic.test', 'path' => '/admin/appointments'],
    ],

    // Honest notes shown on the page: where the system differs from the reports, or a check is not automated.
    'deviations' => [
        ['TC-009', 'The report asks for two delete confirmations. The system uses one confirmation dialog, which is the common pattern. Deleting a slot that has a booked appointment is blocked instead.'],
        ['Security', 'The report plans a log of unauthorized access attempts. Not built yet.'],
        ['Module 4', 'The report deletes records for good. The system uses soft delete, so a deleted record can be restored.'],
        ['Module 4', 'The first-time doctor task (target 2 minutes) needs a real person, so it is timed by hand and not by an automatic test.'],
        ['Load test', 'Measured on one laptop with a single-process PHP server, so the 10 users queue behind each other and the mean is roughly 10 times one request. A real server runs several workers. Measure again on a multi-worker server before judging the 2 second target.'],
        ['Performance', 'Profiling the pages shows database time under 10 ms and at most 10 queries per page, with no N+1. The rest is PHP work such as rendering the page, so heavy pages are the first thing to tune.'],
        ['Performance', 'The Module 4 checks inside PHPUnit measure the application and database. The ApacheBench run measures the web server too.'],
    ],
];
