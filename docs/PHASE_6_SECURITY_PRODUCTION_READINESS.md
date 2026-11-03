# Phase 6 — Security and Production Readiness

## Scope

Phase 6 hardens the existing Laravel IDSS application for controlled deployment. It covers role and data-scope verification, database readiness, secure HTTP/session defaults, PostgreSQL/PostGIS configuration, backups, monitoring hooks, operational documentation and automated verification.

This phase is an engineering readiness baseline. It does not certify the application for live government surveillance or replace Ministry security approval, infrastructure provisioning, penetration testing or operational acceptance.

## Delivered in the repository

### 1. Authorization and data isolation
- Existing server-side role middleware remains the enforcement point; hiding navigation links is not authorization.
- Existing UserDataScope rules restrict farmers to linked farms, field/regional users to assigned administrative areas and descendants, and laboratory users to their assigned laboratories or technician assignments.
- Users without an active area/laboratory assignment receive no scoped records.
- Existing feature tests cover role denial, farmer ownership isolation, empty geographic scope and laboratory assignment isolation.
- Existing report generation/download controls enforce creator/recipient/admin visibility and geographic recipient scope.

### 2. HTTP and session security
- Global middleware adds X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy and Cross-Origin-Opener-Policy.
- HSTS is emitted only for HTTPS requests and is configurable with IDSS_HSTS_ENABLED; do not enable HSTS until the production HTTPS hostname and certificate are active.
- Production session cookies default to Secure and session payload encryption defaults to enabled. Both can be explicitly configured.
- Trusted proxy defaults are restricted to loopback addresses. Production deployments must configure the actual trusted reverse-proxy addresses in TRUSTED_PROXIES; do not blindly trust arbitrary forwarded host/protocol headers.
- Login and public registration routes retain request throttling. Password complexity and administrator-only staff provisioning remain enforced.

### 3. Database readiness
- SQLite remains the Codespaces/development default.
- A PostgreSQL connection is now defined for the SRS production target, with configurable host, port, database, credentials, search path and SSL mode.
- An additive migration adds composite indexes for common case, sample, laboratory result, movement, notification and audit queries. It checks for existing named indexes before creating them.
- /health/idss performs a database readiness query and returns only a minimal status. It does not reveal connection strings or exception messages.
- PostgreSQL production must use PostgreSQL 14+ and have the PostGIS extension installed by the database administrator. The application repository does not invent or install production extensions using an over-privileged application account.

### 4. Database backups
- php artisan idss:backup creates a private backup under storage/app/private/backups by default.
- SQLite uses VACUUM INTO to create a consistent snapshot and then runs PRAGMA integrity_check.
- PostgreSQL uses pg_dump custom format and verifies that pg_restore --list can read the result.
- The command supports a configurable private directory, retention period and binary paths. Old backups created by this command are pruned only after a new backup verifies successfully.
- A daily 02:00 backup is registered with Laravel Scheduler. Set IDSS_BACKUP_TIME to the approved local operational time and run the scheduler continuously.
- Backups must be copied to an encrypted, access-controlled off-host backup vault. A backup left only on the application host does not protect against host loss or ransomware.

### 5. Monitoring and operations
- The readiness endpoint is suitable for a load balancer or external uptime monitor.
- Laravel daily log rotation is configured in the environment template.
- The scheduler runs due reports, approved integration outbox events and database backups. Configure a supervised scheduler process or the platform's scheduler service.
- Database backup, restore, migration and incident runbooks are included below.

## Local Codespaces setup

From the repository root:

~~~bash
cp .env.example .env
php artisan key:generate
touch database/government_idss.sqlite
php artisan optimize:clear
php artisan migrate
php artisan test
~~~

The repository root contains artisan, composer.json, app/, bootstrap/ and database/. Do not change into a second nested Laravel directory.

## Production PostgreSQL/PostGIS configuration

Install and operate PostgreSQL 14+ with PostGIS using the Ministry-approved hosting standard. Provision a dedicated database and least-privilege application role. The DBA should enable PostGIS:

~~~sql
CREATE EXTENSION IF NOT EXISTS postgis;
~~~

Set deployment environment variables (never commit these values):

~~~dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<approved-hostname>
DB_CONNECTION=pgsql
DB_HOST=<database-host>
DB_PORT=5432
DB_DATABASE=government_idss
DB_USERNAME=<least-privilege-application-role>
DB_PASSWORD=<secret-from-secret-manager>
DB_SSLMODE=verify-full
DB_SSLROOTCERT=/path/to/approved-root-ca.pem
DB_SEARCH_PATH=public
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=<approved-proxy-addresses>
LOG_CHANNEL=daily
IDSS_HSTS_ENABLED=true
~~~

Install the PHP pdo_pgsql extension and PostgreSQL client utilities (pg_dump, pg_restore) in the application runtime. Validate TLS certificates and network rules. Run migrations during a controlled deployment window:

~~~bash
php artisan down
php artisan migrate --force
php artisan optimize
php artisan up
~~~

Do not use migrate:fresh or drop/recreate production tables.

## Backup and restore runbook

### Create and verify a backup

~~~bash
php artisan idss:backup
~~~

Optional retention override:

~~~bash
php artisan idss:backup --keep-days=45
~~~

Confirm the command reports a verified backup. Copy it to the approved encrypted off-host backup vault and apply the Ministry retention policy.

### Restore SQLite

1. Put the application into maintenance mode and stop queue/scheduler workers.
2. Preserve the current database file as an incident copy.
3. Copy the selected verified .sqlite backup over the configured SQLite database file.
4. Run php artisan optimize:clear, verify php artisan migrate:status, and run application smoke tests.
5. Bring the application online only after the data owner approves the restored state.

### Restore PostgreSQL

1. Obtain incident/change approval and confirm the target database and restore point.
2. Stop application writes and workers; preserve a snapshot of the current database.
3. Restore the verified custom-format backup with the DBA-approved role, for example:

~~~bash
pg_restore --clean --if-exists --no-owner --no-acl \
  --host="$DB_HOST" --port="$DB_PORT" --username="$DB_USERNAME" \
  --dbname="$DB_DATABASE" /secure/backup/path/idss-db-YYYYMMDD_HHMMSS.dump
~~~

4. Confirm PostGIS is installed, run migration status, verify critical row counts and relationships, and execute smoke tests.
5. Reopen the service only after data-owner and DBA approval.

A restore must be rehearsed in a non-production environment on a defined schedule. The restore command is intentionally not automated by the web application.

## Continuous integration coverage

GitHub Actions runs the feature suite on PHP 8.2, 8.3 and 8.4, provisions PostgreSQL 16 with PostGIS, applies the full migration set against that database, validates the route table, and exercises PostgreSQL backup creation/verification. The feature suite separately runs against in-memory SQLite, including a real SQLite backup integrity and file-permission test.

## Verification commands

~~~bash
composer validate --no-check-publish
php artisan route:list
php artisan migrate:status
php artisan test
php artisan idss:backup
~~~

Readiness endpoint:

~~~bash
curl --fail https://<approved-hostname>/health/idss
~~~

Expected healthy response:

~~~json
{
  "status": "ok",
  "service": "government-idss",
  "checks": {
    "database": "ok"
  }
}
~~~

For PostgreSQL, also verify PostGIS independently using the DBA-approved connection.

## Exit criteria and remaining production gates

| Item | Repository status | External acceptance still required |
|---|---|---|
| Role, farmer, geographic and laboratory access isolation | Existing enforcement and automated tests retained | Ministry approval of role/permission matrix and end-to-end UAT |
| Security headers and production session defaults | Implemented and covered by tests | HTTPS certificate, proxy allow-list and security configuration review |
| PostgreSQL connection | Configured | Provisioned PostgreSQL 14+, pdo_pgsql, TLS, least-privilege role and migration reconciliation |
| PostGIS | Deployment requirement documented | DBA installs extension; official boundaries, SRID and spatial UAT supplied |
| Query indexes | Additive migration implemented | Run and assess on production-sized data |
| Database backup | SQLite/PostgreSQL backup command and scheduler implemented | pg_dump/pg_restore installed; off-host encrypted vault and retention policy configured |
| Recovery | Restore runbooks documented | Timed restore rehearsal and approved recovery-point/recovery-time objectives |
| Health and logs | Readiness endpoint and daily log configuration implemented | External uptime/log monitoring, alert routing and retention configured |
| Performance | Baseline indexes added | Load, concurrency and query-plan testing using realistic Ministry volumes |
| Security assessment | Engineering hardening baseline implemented | Independent vulnerability scan, penetration test, remediation and sign-off |
| Production acceptance | Not certified | Ministry UAT, data governance approval, incident response and formal go-live authorization |

**Phase 6 code deliverables:** HTTP/session hardening, PostgreSQL configuration, operational indexes, readiness endpoint, verified backup command, scheduled backup hook, automated readiness/security-header tests and deployment/restore runbooks.

**Production status:** Not yet production-certified. Live deployment still depends on Ministry infrastructure, approved PostGIS boundary data, secrets/TLS/proxy configuration, off-host backup storage, restore rehearsal, performance and independent security assessment, and Ministry UAT.
