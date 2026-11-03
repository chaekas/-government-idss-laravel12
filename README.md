# Government IDSS — Laravel 12

Integrated Disease Surveillance System (IDSS) for the Ministry of Livestock and Fisheries, aligned to the supplied Software Requirements Specification (SRS).

## Technology
- Laravel 12 / PHP 8.2+
- Blade templates and responsive HTML/CSS
- PostgreSQL 14+ is the recommended production database per the SRS; SQLite is supported for local Codespaces development and automated tests
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
- Administrator-only user management: create accounts, assign roles, link farmer accounts to their registered farmer record, update profiles, deactivate accounts, and reset passwords.
- Audit entries for disease case reporting, user administration, animal registration, investigations, sample movements, laboratory results, and outbreak creation.
- Role-gated reporting dashboard with date-filtered CSV exports for disease cases and sample tracking.
- Authenticated GIS map for farms, cases with georeferenced farms, and outbreak records where coordinates have been entered.
- Farmer accounts are scoped to their linked farm(s) for case listing, reporting, and dashboard counts; staff-only modules are protected by role middleware.
- Laboratory results cannot be approved by the same account that performed the test.
- Automated test configuration and feature tests.

### Important workflow rules
- A sample must be received and quality-accepted before it appears in the laboratory test entry form.
- A rejected sample requires a reason and must not proceed to the laboratory testing form.
- Laboratory results are submitted for review; only a laboratory manager or system administrator can approve or reject them.
- Only the system administrator role can create, edit, deactivate, or assign user accounts.
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

## Role-based access and account creation

The application uses server-side role middleware; hiding a menu item is not treated as authorization.

| Role | Permitted workspace activities |
|---|---|
| Farmer / livestock owner | Self-register; access own farmer dashboard and notification inbox; report cases only against farms linked to their farmer record; view cases linked to their own farms. A field officer must register/link the farm before case reporting is available. |
| Poultry Field Officer (LFO) | Register farmers, farms and animals; investigate cases; collect/register samples; update field case information; record suspected outbreak reports. |
| Laboratory Technician | View sample custody and laboratory records; record test results for received, quality-accepted samples; cannot approve/reject their own result or perform the manager's approval step. |
| Laboratory Manager | Review and approve/reject submitted laboratory results, subject to independent-review checks; view laboratory and sample workflows. |
| Regional Veterinary Officer | Access surveillance cases, farms, investigations, outbreaks, reports and GIS within assigned administrative areas and their active descendant areas. |
| Zonal Laboratory Administrator | View and manage laboratory/sample workflows only for laboratories assigned to the account. |
| MLF Officer | View national surveillance reports, GIS, cases, outbreak records, notifications, samples and laboratory results for oversight. |
| System Administrator | Create/deactivate accounts, assign roles, manage access, review audit records and access authorized administrative workflows. The last active system administrator cannot be demoted or disabled. |

**Registration rule:** The public `/register` route creates a farmer account only. Any submitted `role` value is ignored; the server assigns the `farmer` role and links the account to a new farmer record. Field, laboratory, Ministry and system-administrator accounts must be created by an authenticated system administrator. Public registration is rate-limited and validates unique email and password complexity.

## Known remaining work before production

This repository is an operational development foundation, not a certified production deployment. The following SRS capabilities still require implementation and/or stakeholder verification:
- Final Ministry approval of the role/permission matrix and UAT of the implemented geographic/laboratory scopes. Field and regional users are restricted to assigned administrative areas and descendants; laboratory users are restricted to assigned laboratories; users without assignments receive no scoped records.
- Ministry UAT of the implemented investigation approval, case lifecycle, outbreak linkage and sample-to-result workflows.
- Ministry-approved diagnostic catalogue, test methods/SOPs, QC acceptance criteria, equipment inventory/calibration intervals, electronic signature policy and laboratory UAT. The application workflows for test assignment, independent result review, QC, calibration and CAPA are implemented.
- Real notification delivery through an approved SMS/email gateway; in-app notifications exist, but external SMS/email delivery is not configured.
- Native XLSX workbook generation and server-side PDF rendering are not included; current CSV exports are Excel-compatible and consolidated reports can be printed/saved as PDF in the browser. Scheduled daily/weekly/monthly CSV reports and in-app notifications are implemented.
- Advanced spatial hotspot analysis, official administrative-boundary overlays, PostGIS spatial queries and map export remain. The current GIS map supports disease/species/date/area filters and uses entered case/farm/outbreak coordinates.
- UTAMBUZI integration, which cannot be activated until the Ministry provides the official API specification, authorized credentials, and test environment.
- Password reset delivery, account lockout policy, configurable idle timeout, and additional security review.
- Production database configuration (the SRS specifies PostgreSQL), HTTPS, backups, deployment monitoring, UAT, and formal acceptance.

Do not treat suspected outbreak records or unapproved laboratory findings as official diagnoses. Confirm all clinical and surveillance decisions through authorized Ministry procedures.

## Security and data handling
- Never commit `.env`, credentials, API tokens, or real surveillance records.
- Use HTTPS and the approved government hosting environment in production.
- Configure database backups, retention, monitoring, and role/geographic access controls before handling operational personal or disease data.
- UTAMBUZI integration must use approved authenticated APIs; do not invent endpoints or credentials.
