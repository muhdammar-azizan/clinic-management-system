# Clinic Management System

A Laravel web application for managing clinic patient registration, appointment booking, doctor schedules, and basic medical records.

## Roles

- **Patient**: registers, books appointments, views own medical records.
- **Doctor**: views schedule and appointments, manages medical records.
- **Admin**: manages patients, doctors and the doctor roster.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Demo accounts (password: `password`): `admin@clinic.test`, `doctor@clinic.test`, `patient@clinic.test`.

## Quality

Quality is the focus of this project. See [QUALITY.md](QUALITY.md) for the quality model, test cases, metrics, load test results and the defects found. Run the quality tests with `php artisan test --testsuite=Quality`.
