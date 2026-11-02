# Phase 2 — Disease Surveillance

## Scope and SRS traceability

This phase implements the disease reporting, field investigation, outbreak monitoring, surveillance analytics and in-app alert workflows defined in the supplied IDSS SRS.

| SRS IDs | Requirement area | Delivered implementation |
|---|---|---|
| FR-056–FR-070 | Disease reporting | Unique Case ID; report timestamp; suspected disease; affected species; affected/dead counts; clinical signs; GPS coordinates (case GPS or registered farm GPS); severity; disease status; photo/document evidence; search and filters. |
| FR-071–FR-085 | Disease investigation | Mandatory field-officer assignment; investigation date; field observations; epidemiological findings; possible source; transmission route; biosecurity observations; immediate control measures; recommendations; evidence uploads; status; sample workflow linkage; investigation history; independent approval lock; printable report. |
| FR-143–FR-152 | Surveillance monitoring | Case register monitoring; status distribution; geographic outbreak and case summaries; district hotspots; monthly trends; species distribution; laboratory turnaround summary; epidemiological summaries; searchable case register. |
| FR-153–FR-162 | Alerts and notifications | Suspected outbreak notifications; new case notifications to field/regional roles; incoming sample notifications to laboratory roles; approved-result notifications; in-app notification history; configurable severity/threshold/window/recipient rules; dashboard alert summary. |

## Disease reporting workflow

1. Farmer or authorized surveillance staff submits a suspected disease case.
2. The system generates a unique Case ID and records the report timestamp and reporting channel.
3. The report captures suspected disease, affected species, observed date, symptoms, affected/dead counts and severity.
4. The report captures case GPS coordinates or uses the registered farm coordinates as its location; an animal and disease classification may be linked where known.
5. Optional photographs and PDF evidence are stored on the private local disk and downloaded only through authenticated, role-checked routes.
6. The system writes the initial status history and audit record, then notifies field and regional officers. Configured threshold rules add escalation to Ministry roles.
7. A report can be corrected only before an investigation exists. After investigation starts, the case report is locked.
8. Laboratory approval updates the case status to Confirmed or Rejected. A regional/Ministry officer may close a finalized case with a recorded reason.

## Disease status mapping

The SRS status label “New” is stored as the existing compatible application value `reported`. The remaining workflow values are:

- `under_investigation`
- `sample_collected`
- `laboratory_testing`
- `confirmed`
- `rejected`
- `closed`

Every transition is written to `disease_case_status_history`. Approved laboratory results are the source of Confirmed/Rejected transitions; a suspected report is never automatically presented as a confirmed diagnosis.

## Investigation workflow

- An investigation must reference an existing case and have an active field officer assigned.
- Investigation date cannot precede the disease observation date or be in the future.
- Field observations are mandatory.
- Epidemiological findings, possible source, suspected transmission route, biosecurity observations, immediate control measures and recommendations are captured independently.
- Photographs and documents are uploaded to private storage.
- Each create/update/approval event is recorded in `investigation_histories` and the audit log.
- Completing an investigation requires recommendations. An authorized independent regional/Ministry reviewer approves it; the investigator/assigned officer cannot approve their own report. Approved records are locked.
- The system generates a print-ready investigation report which users can print or save as PDF from the browser.
- Sample registration remains blocked unless an active investigation exists. Sample registration and laboratory reception notify relevant laboratory roles.

## Outbreak monitoring

- Suspected outbreaks can be registered with disease, severity, date, location, GPS and response actions.
- Existing case reports can be associated with an outbreak. When cases are linked, case and death counts are derived from those linked reports and the selected cases must be from the stated district and region.
- The outbreak status lifecycle currently uses `suspected`, `active` and `closed`; confirmation and closure timestamps are retained.
- Outbreak status changes are recorded in `outbreak_status_history`.
- Outbreak records are searchable/filterable and appear on the GIS map where coordinates exist.

## Surveillance analytics

The surveillance analytics page includes:
- Reported, open, confirmed and rejected case counts.
- Investigation and outbreak workload.
- Monthly case trend.
- Case status distribution.
- Disease classification/suspected disease summary.
- Species distribution.
- Geographic hotspot ranking by district, including affected and deceased bird counts.
- Active outbreak distribution by region/district.
- Average time from sample record creation to final release for released samples.
- Unread and recent in-app surveillance/outbreak alerts.

The GIS map now supports filters for disease category, affected species, observation/start date range and administrative area. Case markers use the case's recorded GPS coordinates and fall back to the linked farm coordinates when case coordinates are absent.

## Alert configuration

System Administrators can manage surveillance alert rules under **Master Data → Surveillance alert rules**. Rules define:
- Minimum case severity.
- Number of similar suspected cases required.
- Time window in hours.
- Whether to notify field officers, regional officers and Ministry officers.
- Active/inactive state.

A repeatable seeder creates a default high-severity alert rule. The threshold and recipients can be adjusted by authorized administrators. Notifications remain in the user's in-app inbox with read history.

## Security and data integrity

- Farmer case listing and reporting are limited to farms linked to the farmer account, including legacy primary-owner links and active joint-owner links.
- Field officers and other roles are protected by route/controller role checks.
- Investigation attachments and case attachments are private, not exposed through public storage URLs.
- Approved investigation reports and cases after investigation starts cannot be edited through the case correction workflow.
- Case and investigation status histories preserve actor, time, previous/new status or values and reason.
- Audit logs record case, investigation and outbreak changes.

## Automated acceptance tests

Phase 2 adds feature tests for:
- Disease case capture, evidence upload, status history and configurable alert escalation.
- Case edit lock after investigation begins.
- Investigation date validation, evidence, epidemiological fields, history and independent approval/lock.
- Outbreak-to-case association, recalculated counts and status history.
- Authorized case closure.
- Surveillance analytics access.

## Dependencies / remaining acceptance items

1. **FR-146 incidence and prevalence:** The SRS requires these statistics, but the current farm bird-count field is not a validated, dated population denominator by species and administrative area. The dashboard deliberately does not publish a misleading rate. The Ministry must approve a poultry population baseline/census dataset and its update frequency before incidence/prevalence can be calculated reliably.
2. **FR-158/FR-159 SMS and email:** In-app alerts and history are implemented. Actual SMS/email delivery requires the Ministry-approved SMS gateway, mail service configuration, credentials, templates and delivery/retry policy. No provider or endpoint has been invented.
3. **Notification targeting by jurisdiction:** The current user schema has organization/office assignment foundations but not a complete, authoritative field-officer-to-administrative-area assignment dataset. Notifications are role-targeted; exact district/region recipient routing requires Ministry organization/assignment data.
4. **GIS spatial analysis and official boundaries:** Filters and georeferenced point display are implemented. Spatial hotspot analysis against official administrative polygon boundaries depends on the authoritative boundary dataset and production PostGIS configuration.
5. **Production acceptance:** The local CI environment uses SQLite. PostgreSQL 14+/PostGIS migration, data reconciliation, file storage configuration, mail/SMS gateway tests, security review and Ministry UAT remain separate deployment acceptance activities.

## Phase 2 exit status

**The disease reporting, investigation, outbreak, in-app alert, surveillance analytics and GIS filtering code paths are implemented.** Final SRS acceptance remains conditional on the external data/service dependencies above, especially the population denominator for FR-146 and approved SMS/email services for FR-158/FR-159.
