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

The seeder also adds four more doctors (for example `aisha.rahman@clinic.test`), six more patients, a two-week roster, eight finished visits with medical records, and upcoming and cancelled appointments. The data is fixed, so it looks the same on every run. To start again with fresh demo data: `php artisan migrate:fresh --seed`.

On a real server, create your own admin instead of using the demo accounts: `php artisan create:admin`. It asks for the name, email and password (typing is hidden), and uses the same password rules as registration.

## Run with Docker

This runs the app the way it runs in production: Apache with several PHP workers, and MySQL.

```bash
php artisan key:generate --show      # copy the value
APP_KEY=base64:... docker compose up --build
```

Open <http://localhost:8080>. The demo accounts above are added on the first start. Health check: <http://localhost:8080/health>.

## Deploy on Hostinger (PHP and MySQL)

1. In hPanel, create a MySQL database and user. Set the PHP version to 8.2 or newer.
2. On your computer run `composer install --no-dev --optimize-autoloader` and `npm run build`.
3. Upload the project, including `vendor` and `public/build`. Do not upload `.env`, `node_modules` or `tests`.
4. Point the domain's document root to the `public` folder.
5. Copy [.env.production.example](.env.production.example) to `.env` on the server and fill in `APP_KEY`, `APP_URL` and the database values.
6. Over SSH run `php artisan migrate --force`, then `php artisan db:seed --force` once for the demo accounts, then `php artisan optimize`.
7. Open `/health`. It must answer `{"status":"ok"}`.

When you upload a new version, keep `.env` and the `storage/app/private` folder, because that is where medical record files are saved.

## Quality

Quality is the focus of this project. See [QUALITY.md](QUALITY.md) for the quality model, test cases, metrics, load test results and the defects found. Run the quality tests with `php artisan test --testsuite=Quality`.
