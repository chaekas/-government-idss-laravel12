# Government IDSS — Laravel 12

Initial implementation of the Integrated Disease Surveillance System for the Ministry of Livestock and Fisheries, based on the uploaded IDSS Software Requirements Specification.

## Stack
- Laravel 12 / PHP 8.2+
- MySQL
- Blade templates, HTML5 and CSS3

## Implemented foundation
- Session-based sign-in/sign-out; public self-registration is disabled.
- Protected dashboard with live counts and recent disease reports.
- Farmer registration and listing.
- Farm registration and listing, including administrative location and optional coordinates.
- Disease case reporting and listing with server-side validation.
- Initial database schema for users/roles, farmers, farms, disease cases, laboratories and samples.
- Responsive dashboard and forms.

This is a foundation, not yet a complete production system. Full role/permission enforcement, disease investigation workflow, poultry inventory, sample collection/transport, laboratory testing and quality-control/approval, notifications, GIS visualization, comprehensive reports, audit trails, and UTAMBUZI integration remain to be implemented. UTAMBUZI integration requires its official API specification and authorized credentials.

## Run locally
1. Install PHP 8.2+, Composer, and MySQL.
2. Clone this repository and run `composer install`.
3. Copy `.env.example` to `.env`.
4. Create a MySQL database named `government_idss` (or your configured name).
5. Run `php artisan key:generate`.
6. Run `php artisan migrate`.
7. Provision the first authorized user through a secure, controlled admin process. Do not use a public/default password.
8. Run `php artisan serve` and open the shown URL.

Set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.

## Security
Never commit `.env`, credentials, API tokens, or real surveillance records. Use HTTPS in deployment. Enforce approved role and geographic access controls, backups, retention, and Ministry data-protection requirements before production use.
