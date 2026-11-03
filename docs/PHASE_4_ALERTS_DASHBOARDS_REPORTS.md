# Phase 4 — Alerts, Dashboards and Reports

## Scope

This phase strengthens the reporting and decision-support layer on top of the disease surveillance and laboratory workflows.

## Delivered

### 1. Dashboard data integrity
- Farmer dashboard farm/case/animal counts now include farms where the farmer is a current joint owner, not only the legacy primary-owner field.
- Existing role-oriented dashboard modes remain in place for farmer, laboratory and surveillance users.
- Surveillance analytics include case status, disease/species distribution, monthly trend, geographic district ranking, outbreak distribution and sample turnaround summaries.

### 2. Report exports
- Disease case register CSV.
- Sample tracking and chain-of-custody status CSV.
- Laboratory diagnostic result CSV.
- Outbreak register CSV.
- Date filters are applied consistently to report totals and exports.
- CSV files include a UTF-8 BOM for spreadsheet compatibility and can be opened in Microsoft Excel or other spreadsheet tools.

### 3. Print-ready management report
- A consolidated report view includes cases, samples, laboratory results and outbreaks.
- The page is print-optimized and includes a **Print / Save as PDF** action. Users can choose “Save as PDF” in their browser's print dialog.
- The view caps each section at 500 rows to keep browser-based print rendering practical.

### 4. Scheduled reports
- Authorized leadership/system users can schedule case, sample, laboratory-result or outbreak reports daily, weekly or monthly.
- Schedules support date filters and one or more authorized user recipients.
- Laravel's scheduler runs due schedules, writes CSV files to private local storage, creates generated-report records and sends in-app notifications to recipients.
- Generated reports are available through authenticated, recipient-checked download routes. The report archive lists only schedules and generated files created by or shared with the signed-in user (administrators can oversee all). Each scheduled file is generated using the creator's current geographic/laboratory data scope; regional schedules cannot be shared with recipients outside the creator's overlapping area or national oversight roles.

### 5. Access and confidentiality
- Report pages and exports remain restricted to regional veterinary, Ministry and system administrator roles.
- Generated report files are stored on the private local disk and are not exposed through public URLs.
- Only administrators, the report creator or configured recipients can download a generated report.

## Automated acceptance tests

The test suite covers:
- Report schedule creation and geographic recipient validation.
- Creator-scoped scheduled CSV content and private report archive visibility.
- Due schedule execution and private CSV generation.
- In-app notification delivery.
- Print report and result/outbreak export access.
- Non-leadership report access denial.
- Farmer dashboard current joint-owner farm inclusion is covered by the dashboard query behavior and remains subject to role-scope tests.

## Operational requirements

- Keep the Laravel scheduler running in the target environment. For local demonstration, run php artisan schedule:work; in production use the host's cron/scheduler service.
- Configure private storage persistence and backup for generated reports.
- CSV is the spreadsheet export format; it is Excel-compatible but is not a native .xlsx workbook.
- PDF output is produced by the browser's print-to-PDF function; server-side PDF generation requires an approved PDF package/runtime.
- Actual SMS/email distribution remains dependent on approved Ministry gateway/service credentials and templates.

## Phase 4 exit status

**Dashboard corrections, CSV exports, print-ready report, scheduled report generation and in-app recipient notifications are implemented in code.** Native XLSX/server-generated PDF and external SMS/email delivery are documented as deployment enhancements requiring approved runtime/service configuration.
