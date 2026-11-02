# Government IDSS — Laravel 12

Integrated Disease Surveillance System (IDSS) for the Ministry of Livestock and Fisheries, aligned to the supplied Software Requirements Specification (SRS).

## Technology
- Laravel 12 / PHP 8.2+
- Blade templates and responsive HTML/CSS
- MySQL for deployment; SQLite is supported for local Codespaces development and automated tests
- Server-rendered forms with Laravel validation, CSRF protection, session authentication, and authorization middleware

## Current implementation

### Core registers
- Secure sign-in/sign-out; public self-registration is disabled.
- Protected surveillance dashboard with farmer, farm, animal, case, investigation, sample, and outbreak counts.
- Farmer registration and listing with generated Farmer IDs.
- Farm registration and listing, including administrative location and optional GPS coordinates.
- Poultry/animal registry linked to farms, generated Animal IDs, external identifiers, species, breed, and notes.
- Disease case reporting with date, symptom, affected/dead count, and severity validation.
- Disease investigation assignment, field observations, epidemiological findings, recommended actions, and GPS coordinates.
- Sample registration with generated Sample ID/barcode value, laboratory assignment, quality status, transport state, and movement history.
- Laboratory facility registration and laboratory test result entry for received, quality-accepted samples.
- Laboratory result review/approval restricted to laboratory managers and system administrators.
- Outbreak register with disease, location, case/death counts, severity, coordinates, and response notes.
- Administrator-only user management: create accounts, assign roles, update profiles, deactivate accounts, and reset passwords.
- Audit entries for user administration, animal registration, investigations, sample movements, laboratory results, and outbreak creation.
- Automated test configuration and feature tests.

### Important workflow rules
- A sample must be received and quality-accepted before it appears in the laboratory test entry form.
- A rejected sample requires a reason and must not proceed to the laboratory testing form.
- Laboratory results are submitted for review; only a laboratory manager or system administrator can approve or reject them.
- User administration is restricted to system administrators/authorized administrator roles.
- Generated IDs are operational identifiers; they do not replace official national identifiers or UTAMBUZI identifiers.

## Run in GitHub Codespaces

From the inner Laravel application folder:

```bash
cd /workspaces/-government-idss-laravel12/-government-idss-laravel12
composer install
```

If `.env` does not exist:

```bash
cp .env.example .env
```

Generate the app key once:

```bash
php artisan key:generate
```

For Codespaces without a MySQL service, configure SQLite in `.env`:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/workspaces/-government-idss-laravel12/-government-idss-laravel12/database/government_idss.sqlite
SESSION_DRIVER=database
CACHE_STORE=file
```

Create the local database file and run migrations:

```bash
touch database/government_idss.sqlite
php artisan config:clear
php artisan migrate
```

Create the first administrator interactively. Use a strong, unique password of at least 12 characters containing letters and numbers:

```bash
php artisan idss:create-admin
```

Then start the app:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Open port 8000 from the Codespaces **Ports** tab.

## Tests and checks

```bash
php artisan test
php artisan route:list
php artisan migrate:status
```

Tests use an in-memory SQLite database configured in `phpunit.xml.dist`, so they should not overwrite the local development database.

## Known remaining work before production

This repository is an operational development foundation, not a certified production deployment. The following SRS capabilities still require implementation and/or stakeholder verification:
- Full role/permission matrix and administrative/geographic record-level scoping for every module.
- Formal disease investigation sign-off and richer case lifecycle/status transitions.
- Diagnostic test master catalogue, sample quality control inspections, equipment calibration, corrective actions, and immutable approved-result records.
- Real notification delivery through an approved SMS/email gateway; in-app notifications exist, but external SMS/email delivery is not configured.
- PDF/Excel report generation, scheduled reporting, and complete executive/laboratory performance analytics.
- Interactive GIS maps, hotspot analysis, and map filtering.
- UTAMBUZI integration, which cannot be activated until the Ministry provides the official API specification, authorized credentials, and test environment.
- Password reset delivery, account lockout policy, configurable idle timeout, and additional security review.
- Production database configuration (the SRS specifies PostgreSQL), HTTPS, backups, deployment monitoring, UAT, and formal acceptance.

Do not treat suspected outbreak records or unapproved laboratory findings as official diagnoses. Confirm all clinical and surveillance decisions through authorized Ministry procedures.

## Security and data handling
- Never commit `.env`, credentials, API tokens, or real surveillance records.
- Use HTTPS and the approved government hosting environment in production.
- Configure database backups, retention, monitoring, and role/geographic access controls before handling operational personal or disease data.
- UTAMBUZI integration must use approved authenticated APIs; do not invent endpoints or credentials.
