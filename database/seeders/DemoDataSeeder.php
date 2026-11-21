<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\ConditionStatus;
use App\Enums\Role;
use App\Enums\SlotStatus;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\MedicalRecord;
use App\Models\RosterSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Fills the clinic with believable data so every page has something to show:
 * more doctors and patients, a two-week roster, past and upcoming appointments
 * and medical records. The data is fixed (no random values), so every run looks the same.
 * All passwords are "password". Fake data only, no real people.
 */
class DemoDataSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: string, 3: string}> */
    private const EXTRA_DOCTORS = [
        ['Dr. Aisha Rahman', 'aisha.rahman@clinic.test', 'Pediatrician', 'Caring for children from newborn to teenage years.'],
        ['Dr. Daniel Tan', 'daniel.tan@clinic.test', 'Family Medicine', 'Long-term care for families and chronic conditions.'],
        ['Dr. Sofia Lim', 'sofia.lim@clinic.test', 'Gynecologist', 'Women health, pregnancy care and screening.'],
        ['Dr. Hafiz Karim', 'hafiz.karim@clinic.test', 'General Practitioner', 'Everyday illness, check-ups and health advice.'],
    ];

    /** @var array<int, array{0: string, 1: string}> */
    private const EXTRA_PATIENTS = [
        ['Nur Aina', 'nur.aina@clinic.test'],
        ['Lee Wei Han', 'lee.weihan@clinic.test'],
        ['Priya Nair', 'priya.nair@clinic.test'],
        ['Ahmad Zaki', 'ahmad.zaki@clinic.test'],
        ['Sarah Wong', 'sarah.wong@clinic.test'],
        ['Farid Hamdan', 'farid.hamdan@clinic.test'],
    ];

    /** @var array<int, array{0: string, 1: string, 2: string, 3: string, 4: string}> */
    private const VISITS = [
        ['Sore throat and fever', 'Fever for two days, painful swallowing', 'Acute tonsillitis', 'Rest, warm fluids and a short antibiotic course.', 'Amoxicillin 500mg, three times daily for 5 days.'],
        ['Persistent cough', 'Dry cough for one week, no fever', 'Upper respiratory infection', 'Drink plenty of water and avoid cold drinks.', 'Cough syrup, three times daily.'],
        ['Routine check-up', 'No complaints', 'Healthy, blood pressure normal', 'Keep up regular exercise and a balanced diet.', 'No medicine needed.'],
        ['Headache and dizziness', 'Headache in the afternoon, mild dizziness', 'Tension headache', 'Reduce screen time and sleep at least seven hours.', 'Paracetamol 500mg when needed.'],
        ['Skin rash', 'Itchy rash on both arms for three days', 'Contact dermatitis', 'Avoid the new soap and keep the skin moisturised.', 'Hydrocortisone cream, twice daily for 7 days.'],
        ['Stomach pain', 'Pain after meals, mild nausea', 'Gastritis', 'Eat small meals and avoid spicy food.', 'Antacid tablets after meals.'],
        ['Back pain', 'Lower back pain after lifting', 'Muscle strain', 'Gentle stretching and avoid heavy lifting for two weeks.', 'Ibuprofen 200mg after meals when needed.'],
        ['Blood pressure review', 'Follow-up of high blood pressure', 'Hypertension, improving', 'Reduce salt and continue daily walks.', 'Amlodipine 5mg once daily.'],
    ];

    private const REASONS = [
        'Fever and sore throat', 'Follow-up visit', 'Health screening', 'Cough that will not go away',
        'Skin problem', 'Stomach pain', 'Headache', 'Blood pressure check',
    ];

    public function run(): void
    {
        $services = Service::query()->pluck('id', 'name');
        $doctors = $this->doctors($services);
        $patients = $this->patients();

        $this->rosterFor($doctors);
        $this->pastVisits($doctors, $patients);
        $this->upcomingAppointments($doctors, $patients);
    }

    /**
     * The demo doctor from DatabaseSeeder plus the extra doctors.
     *
     * @param  Collection<string, int>  $services
     * @return Collection<int, User>
     */
    private function doctors($services)
    {
        foreach (self::EXTRA_DOCTORS as $index => [$name, $email, $service, $bio]) {
            $doctor = User::factory()->create(['name' => $name, 'email' => $email, 'role' => Role::Doctor]);

            DoctorProfile::create([
                'user_id' => $doctor->id,
                'service_id' => $services[$service],
                'license_number' => 'MMC-'.str_pad((string) ($index + 2), 5, '0', STR_PAD_LEFT),
                'bio' => $bio,
            ]);
        }

        return User::query()->where('role', Role::Doctor)->orderBy('id')->get();
    }

    /**
     * The demo patient from DatabaseSeeder plus the extra patients.
     *
     * @return Collection<int, User>
     */
    private function patients()
    {
        foreach (self::EXTRA_PATIENTS as $index => [$name, $email]) {
            User::factory()->create([
                'name' => $name,
                'email' => $email,
                'role' => Role::Patient,
                'phone' => '01'.(20000000 + $index * 1111111),
            ]);
        }

        return User::query()->where('role', Role::Patient)->orderBy('id')->get();
    }

    /**
     * Four half-hour slots a day on weekdays, for the next two weeks, for every doctor.
     * Doctors take turns between the morning and the afternoon.
     *
     * @param  Collection<int, User>  $doctors
     */
    private function rosterFor($doctors): void
    {
        $morning = [['09:00', '09:30'], ['09:30', '10:00'], ['10:00', '10:30'], ['10:30', '11:00']];
        $afternoon = [['14:00', '14:30'], ['14:30', '15:00'], ['15:00', '15:30'], ['15:30', '16:00']];

        foreach ($doctors as $doctorIndex => $doctor) {
            for ($day = 1; $day <= 14; $day++) {
                $date = today()->addDays($day);

                if ($date->isWeekend()) {
                    continue;
                }

                $times = ($doctorIndex + $day) % 2 === 0 ? $morning : $afternoon;

                foreach ($times as [$start, $end]) {
                    RosterSlot::create([
                        'doctor_id' => $doctor->id,
                        'slot_date' => $date->toDateString(),
                        'start_time' => $start,
                        'end_time' => $end,
                        'status' => SlotStatus::Available,
                    ]);
                }
            }
        }
    }

    /**
     * Finished visits in the last month, each with a medical record.
     *
     * @param  Collection<int, User>  $doctors
     * @param  Collection<int, User>  $patients
     */
    private function pastVisits($doctors, $patients): void
    {
        foreach (self::VISITS as $index => [$complaint, $symptoms, $diagnosis, $plan, $prescription]) {
            $doctor = $doctors[$index % $doctors->count()];
            $patient = $patients[$index % $patients->count()];
            $date = today()->subDays(3 + $index * 3);

            $slot = RosterSlot::create([
                'doctor_id' => $doctor->id,
                'slot_date' => $date->toDateString(),
                'start_time' => '10:00',
                'end_time' => '10:30',
                'status' => SlotStatus::Occupied,
            ]);

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'roster_slot_id' => $slot->id,
                'service_id' => $doctor->doctorProfile->service_id,
                'status' => AppointmentStatus::Completed,
                'reason' => self::REASONS[$index],
            ]);

            MedicalRecord::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_id' => $appointment->id,
                'visit_date' => $date->toDateString(),
                'chief_complaint' => $complaint,
                'symptoms' => $symptoms,
                'diagnosis' => $diagnosis,
                'treatment_plan' => $plan,
                'prescription' => $prescription,
                'condition' => $index === 7 ? ConditionStatus::Recovering : ConditionStatus::Stable,
                'follow_up_date' => $index % 3 === 0 ? $date->copy()->addDays(14)->toDateString() : null,
            ]);
        }
    }

    /**
     * Booked appointments on the coming days, and two cancelled ones.
     * The demo patient always has something upcoming to look at.
     *
     * @param  Collection<int, User>  $doctors
     * @param  Collection<int, User>  $patients
     */
    private function upcomingAppointments($doctors, $patients): void
    {
        $slots = RosterSlot::query()
            ->where('status', SlotStatus::Available)
            ->whereDate('slot_date', '>', today())
            ->orderBy('slot_date')->orderBy('start_time')->orderBy('doctor_id')
            ->get()
            ->values();

        // Every 5th slot is booked, which spreads the bookings over doctors and days.
        $taken = $slots->filter(fn ($slot, $position) => $position % 5 === 0)->values()->take(14);

        foreach ($taken as $position => $slot) {
            // The first booking belongs to the demo patient.
            $patient = $position === 0 ? $patients->first() : $patients[$position % $patients->count()];
            $doctor = $doctors->firstWhere('id', $slot->doctor_id);
            $cancelled = $position === 5 || $position === 9;

            $slot->update(['status' => $cancelled ? SlotStatus::Available : SlotStatus::Occupied]);

            Appointment::create([
                'patient_id' => $patient->id,
                'roster_slot_id' => $slot->id,
                'service_id' => $doctor->doctorProfile->service_id,
                'status' => $cancelled ? AppointmentStatus::Cancelled : AppointmentStatus::Booked,
                'reason' => self::REASONS[$position % count(self::REASONS)],
            ]);
        }
    }
}
