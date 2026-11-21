# Quality

This project is a rebuild of a university Software Quality Assurance (SQA) project. The first version was a WordPress system. This version is built with Laravel, and quality is the main thing it sets out to show: every quality claim here comes from a test or a measurement that anyone can run again.

The tables further down are generated from saved results, so the numbers cannot drift away from the code. The sections around them are written by hand.

- [Quality model](#quality-model)
- [Attributes by module](#attributes-by-module)
- [How to run the checks](#how-to-run-the-checks)
- [Results](#results)
- [Defects found while testing](#defects-found-while-testing)
- [Checks done by a person](#checks-done-by-a-person)
- [Not done yet](#not-done-yet)

## Quality model

- **ISO/IEC 25010** (software product quality) chooses the attributes.
- **IEEE 730** (software quality assurance plans) shapes the plan: risks, scenarios, metrics, records and non-conformance handling.
- **Nielsen's usability heuristics** and Shneiderman's error-prevention rule guide the interface. Examples: visible system status (success and error messages), error prevention (validation and confirmation before delete), recognition over recall (labelled fields).
- Each attribute is written as a **quality scenario** (source, stimulus, environment, artifact, response, response measure) and answered with **tactics**, as in the original reports.

## Attributes by module

| Module | Attributes measured | Where it is measured |
| --- | --- | --- |
| 1. Patient profile and registration | Plan not added yet (see below) | Feature tests only |
| 2. Appointment booking | Plan not added yet (see below) | Feature tests only |
| 3. Doctor roster | Usability, Accuracy, Data Integrity, Security | `tests/Quality/Module3`, test cases TC-001 to TC-013 |
| 4. Medical records | Performance, Reliability, Usability | `tests/Quality/Module4`, ApacheBench load test |

Modules 1 and 2 are built and covered by ordinary feature tests (for example, a slot cannot be booked twice, and a patient cannot cancel someone else's appointment). They have no quality plan yet, so they have no metrics and are not claimed as measured.

Beyond the attributes above, three rules apply to the whole system and are tested for all roles:

- **Role-based access.** A test calls every protected route as a visitor and as every wrong role. A route added without role protection fails the build.
- **Policies.** Appointments, medical records and attachments are checked through policies. The admin has no access to clinical content.
- **Private files.** Medical files are stored on a private disk and can only be downloaded through a checked route.

## How to run the checks

| Command | What it does |
| --- | --- |
| `php artisan test` | The whole test suite. It always uses its own in-memory database, never the development database. |
| `php artisan test --testsuite=Quality` | Only the tests written from the SQA reports. |
| `php artisan test --group=TC-006` | One report test case. Groups also exist for requirements, for example `SRS-REQ-309`. |
| `php artisan quality:report` | Runs the Quality suite and saves `quality/results.json`. Add `--all` to also record the whole suite. |
| `php artisan quality:load-test` | Opens five pages with ApacheBench while signed in and saves `quality/load-test.json`. The app must be running. Use `--url`, `--requests` and `--concurrency` to change the run. |
| `php artisan quality:document` | Rewrites the generated tables below. `--check` only reports whether they are out of date. |

The admin can read the same results on the **Quality report** page (account menu, admin only).

Tools: PHPUnit 11 with Laravel's test tools, SQLite in memory for tests, ApacheBench for load, and the in-app portal feedback for user ratings.

## Results

<!-- quality:generated:start -->

_This block is written by `php artisan quality:document` from `quality/results.json` and `quality/load-test.json`. Do not edit it by hand._

### Summary

| Item | Value |
| --- | --- |
| Quality tests passed | 38 of 38 |
| Results saved | 2026-11-21 |
| Commit | `8962e81` |
| Runtime | PHP 8.2.31, Laravel 12.72.0 |
| Test database | SQLite in memory (tests) |

### Metrics, Module 3: Doctor Roster

| Attribute | Metric | Target | Result | Status |
| --- | --- | --- | --- | --- |
| Usability | Task Completion Rate | > 90% | 100% | Met |
| Usability | User Feedback Score | >= 2.5 / 3 | - | No data |
| Accuracy | Accuracy Rate | = 100% | 100% | Met |
| Accuracy | Expired Slot Error Rate | = 0% | 0% | Met |
| Data Integrity | Duplicate Conflict Rate | < 5% | 0% | Met |
| Data Integrity | Data Consistency Rate | = 100% | 100% | Met |
| Security | Unauthorized Access Prevention Rate | > 95% | 100% | Met |
| Security | Permission Error Rate | < 5% | 0% | Met |

### Metrics, Module 4: Medical Records

| Attribute | Metric | Target | Result | Status |
| --- | --- | --- | --- | --- |
| Performance | Performance checks passed | = 100% | 100% | Met |
| Performance | Slowest mean response under load | < 2000 ms | 3200.2 ms | Not met |
| Reliability | Reliability checks passed | = 100% | 100% | Met |
| Reliability | Failed requests under load | = 0 | 0 | Met |
| Usability | Usability checks passed | = 100% | 100% | Met |
| Usability | Data Accurate Rate | = 100% | 100% | Met |

### Module 3 test cases

| ID | Attribute | Scenario | Requirements | Automated checks | Status |
| --- | --- | --- | --- | --- | --- |
| TC-001 | Usability | Admin creates a new schedule slot | 301, 302 | 1 | Pass |
| TC-002 | Usability | Admin views slots in list view and calendar view | 302, 303, 306 | 1 | Pass |
| TC-003 | Usability | Admin edits an existing slot | 304 | 1 | Pass |
| TC-004 | Usability | Admin searches and sorts the schedule list | 308 | 1 | Pass |
| TC-005 | Accuracy | System blocks a slot with a past date | 309, 310 | 2 | Pass |
| TC-006 | Accuracy | System blocks a slot with a past time today | 309, 310 | 1 | Pass |
| TC-007 | Accuracy | System accepts a valid future slot | 309, 310 | 2 | Pass |
| TC-008 | Data Integrity | System prevents a duplicate slot | 307 | 2 | Pass |
| TC-009 | Data Integrity | Admin deletes a slot after confirmation | 305 | 2 | Pass |
| TC-010 | Data Integrity | Same doctor and date with a different time is allowed | 307 | 1 | Pass |
| TC-011 | Security | Doctor and patient are denied the roster pages | 311, 312 | 3 | Pass |
| TC-012 | Security | Admin has full create, edit and delete access | 311, 312 | 1 | Pass |
| TC-013 | Security | Doctor can only view their own schedule | 311, 312 | 2 | Pass |

### Requirement coverage (SRS-REQ-301 to 312)

| Requirement | Meaning | Test cases | Status |
| --- | --- | --- | --- |
| 301 | Admin creates a slot (doctor, date, start and end time, status) | 001 | Pass |
| 302 | Admin views a list of all slots | 001, 002 | Pass |
| 303 | Slot details and availability status are shown | 002 | Pass |
| 304 | Admin edits a slot | 003 | Pass |
| 305 | Admin deletes a slot | 009 | Pass |
| 306 | List view and calendar view | 002 | Pass |
| 307 | No two slots of one doctor overlap | 008, 010 | Pass |
| 308 | Search, filter and sort | 004 | Pass |
| 309 | Expired slots are never offered for booking | 005, 006, 007 | Pass |
| 310 | Past slots are rejected, even when added by the admin | 005, 006, 007 | Pass |
| 311 | Only the admin manages the roster, doctors view their own | 011, 012, 013 | Pass |
| 312 | Role-based access control | 011, 012, 013 | Pass |

### Module 4 checks

| ID | Attribute | Check | Tests | Status |
| --- | --- | --- | --- | --- |
| M4-PERF-1 | Performance | Indexes exist and the query count does not grow with the data | 3 | Pass |
| M4-PERF-2 | Performance | Pages answer well within 2 seconds | 2 | Pass |
| M4-REL-1 | Reliability | Foreign keys refuse links to people who do not exist | 2 | Pass |
| M4-REL-2 | Reliability | Delete needs confirmation and loses no data | 4 | Pass |
| M4-REL-3 | Reliability | A failed save rolls back and leaves no orphan file | 1 | Pass |
| M4-REL-4 | Reliability | 100 deletes and 100 reads without a failure | 2 | Pass |
| M4-USE-1 | Usability | Clear labels, required fields marked, form prefilled | 2 | Pass |
| M4-USE-2 | Usability | Specific error messages and nothing typed is lost | 1 | Pass |
| M4-USE-3 | Usability | Clear success message after saving | 2 | Pass |
| M4-USE-4 | Usability | Data Accurate Rate of 100% | 1 | Pass |

### Load test

ApacheBench (ab -n 100 -c 10) against http://127.0.0.1:8001. Server: Single-process PHP built-in server, OPcache on and Laravel optimize (cached config, routes and views), Windows laptop, SQLite. Data: 200 medical records, 180 roster slots, 30 appointments.

| Page | Mean (ms) | 95% within (ms) | Requests/s | One request (ms) | Failed |
| --- | --- | --- | --- | --- | --- |
| Doctor: medical record list | 2580.5 | 2845 | 3.88 | 258 | 0 |
| Doctor: weekly schedule | 1584.7 | 2468 | 6.31 | 158 | 0 |
| Patient: medical record list | 1643.1 | 1744 | 6.09 | 164 | 0 |
| Admin: roster list | 3200.2 | 4273 | 3.12 | 321 | 0 |
| Admin: appointments | 2494.9 | 2640 | 4.01 | 249 | 0 |

### Deviations and limits

- **TC-009:** The report asks for two delete confirmations. The system uses one confirmation dialog, which is the common pattern. Deleting a slot that has a booked appointment is blocked instead.
- **Security:** The report plans a log of unauthorized access attempts. Not built yet.
- **Module 4:** The report deletes records for good. The system uses soft delete, so a deleted record can be restored.
- **Module 4:** The first-time doctor task (target 2 minutes) needs a real person, so it is timed by hand and not by an automatic test.
- **Load test:** Measured on one laptop with a single-process PHP server, so the 10 users queue behind each other and the mean is roughly 10 times one request. A real server runs several workers. Measure again on a multi-worker server before judging the 2 second target.
- **Performance:** Profiling the pages shows database time under 10 ms and at most 10 queries per page, with no N+1. The rest is PHP work such as rendering the page, so heavy pages are the first thing to tune.
- **Performance:** The Module 4 checks inside PHPUnit measure the application and database. The ApacheBench run measures the web server too.

<!-- quality:generated:end -->

## Defects found while testing

Writing the quality tests found real problems. They are recorded here the way the SQA plan asks: what was found, why, what fixed it, and which test guards it.

| # | Problem | Cause | Fix | Guarded by |
| --- | --- | --- | --- | --- |
| 1 | The duplicate-slot check never matched | The slot date was stored with a time part, so equal dates looked different | Store the date as a plain date | TC-008 |
| 2 | A slot with a start time already passed today was accepted | Only the date was checked (report case TC-006) | Check the time when the date is today | TC-006 |
| 3 | The roster list could not be sorted | Sorting was not built (report case TC-004) | Sort by date, latest added, and doctor A to Z | TC-004 |
| 4 | Overlapping slots of one doctor were accepted | Only an identical start time was blocked, and slots had no end time (SRS-REQ-301 and 307) | Added end time and an overlap rule | TC-008 |
| 5 | A failed save could leave an uploaded file behind | The file path was remembered only after the database step succeeded | Remember the path before touching the database | M4-REL-3 |
| 6 | The doctor's record list had no matching index | Only a patient and date index existed | Added an index on doctor and visit date | M4-PERF-1 |
| 7 | Running the tests erased the development database | `phpunit.xml` did not set a test database | Tests use an in-memory database | Every test run |
| 8 | The report command ran tests with development settings | Values from `.env` override `phpunit.xml` | The command passes the `phpunit.xml` settings explicitly | `quality:report` |
| 9 | Two tests failed now and then | Random fake data sometimes contained the text being searched for | Use fixed names and emails | The two tests that were fixed |
| 10 | Dashboard views would fail on a Linux server | View names used capital letters (`Admin.dashboard`) but folders are lower case | Lower-case view names | Dashboard tests |
| 11 | **Open.** Pages are slow under 10 users at once | See the load test and the limits above | To be measured again on a server with several workers | Load test |

## Checks done by a person

Two checks in the original reports need a human, so they are timed by hand and recorded here. Fill in the results and commit them.

**Module 4: first-time doctor adds a medical record.** Target: finished within 2 minutes, without help or a manual.

1. Open an appointment that has started and choose **Write medical record**.
2. Complete the three steps and save.
3. Record the time, and whether the person asked for help.

| Person | Date | Time (seconds) | Asked for help | Finished |
| --- | --- | --- | --- | --- |
| | | | | |

**Module 3: usability of the roster.** Target: users finish each task without assistance, and the portal feedback score is 2.5 or more out of 3.

1. Create a slot, switch between list and calendar view, edit the slot, search for a doctor.
2. Afterwards, choose **Give feedback** in the account menu, pick *Doctor roster*, and rate Easy, Moderate or Hard.

| Person | Tasks finished without help | Rating |
| --- | --- | --- |
| | | |

## Not done yet

- Quality plans for Modules 1 and 2. The original reports for them have not been added.
- **Load test on a server with several workers.** The current numbers come from a single-process server and are a worst case for one laptop.
- Accessibility and performance audit with Lighthouse.
- Automated security scan (for example OWASP ZAP).
- A log of unauthorized access attempts, which the original security plan lists.
- Running these checks in continuous integration. `php artisan quality:document --check` is ready for it.
