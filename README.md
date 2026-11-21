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

## Run with Docker

This runs the app the way it runs in production: Apache with several PHP workers, and PostgreSQL.

```bash
php artisan key:generate --show      # copy the value
APP_KEY=base64:... docker compose up --build
```

Open <http://localhost:8080>. The demo accounts above are added on the first start. Health check: <http://localhost:8080/health>. For a hosted copy, set the variables listed in [.env.production.example](.env.production.example) and keep medical record files on a persistent volume at `storage/app/private`.

## Quality

Quality is the focus of this project. See [QUALITY.md](QUALITY.md) for the quality model, test cases, metrics, load test results and the defects found. Run the quality tests with `php artisan test --testsuite=Quality`.
