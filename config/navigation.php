<?php

/*
|--------------------------------------------------------------------------
| Header navigation per role
|--------------------------------------------------------------------------
|
| Each item has a label, a route name and a route pattern used to mark the
| item as active. Items whose route does not exist yet are hidden
| automatically, so new pages only need to be registered with the route
| name listed here to appear in the header.
|
*/

return [
    'admin' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Patients', 'route' => 'admin.patients.index', 'active' => 'admin.patients.*'],
        ['label' => 'Doctors', 'route' => 'admin.doctors.index', 'active' => 'admin.doctors.*'],
        ['label' => 'Appointments', 'route' => 'admin.appointments.index', 'active' => 'admin.appointments.*'],
        ['label' => 'Doctor Rosters', 'route' => 'admin.rosters.index', 'active' => 'admin.rosters.*'],
        ['label' => 'Medical Records', 'route' => 'admin.records.index', 'active' => 'admin.records.*'],
    ],

    'doctor' => [
        ['label' => 'Dashboard', 'route' => 'doctor.dashboard', 'active' => 'doctor.dashboard'],
        ['label' => 'My Patients', 'route' => 'doctor.patients.index', 'active' => 'doctor.patients.*'],
        ['label' => 'Appointments', 'route' => 'doctor.appointments.index', 'active' => 'doctor.appointments.*'],
        ['label' => 'Schedule', 'route' => 'doctor.schedule.index', 'active' => 'doctor.schedule.*'],
        ['label' => 'Medical Records', 'route' => 'doctor.records.index', 'active' => 'doctor.records.*'],
    ],

    'patient' => [
        ['label' => 'Home', 'route' => 'patient.dashboard', 'active' => 'patient.dashboard'],
        ['label' => 'Book Appointment', 'route' => 'patient.booking.create', 'active' => 'patient.booking.*'],
        ['label' => 'My Appointments', 'route' => 'patient.appointments.index', 'active' => 'patient.appointments.*'],
        ['label' => 'My Medical Records', 'route' => 'patient.records.index', 'active' => 'patient.records.*'],
    ],
];
