# IDSS SRS Implementation Matrix

This matrix tracks the Laravel repository against the current 79-page IDSS SRS. “Foundation implemented” means code and schema exist; it does not mean the requirement has passed Ministry UAT or production acceptance.

| SRS capability | Current state | Notes / acceptance evidence still needed |
|---|---|---|
| Authentication and sessions (FR-001–FR-012) | Partial | Sign-in/out and login throttling exist. Configurable account lockout/unlock, full login audit history, idle timeout and password reset delivery remain. |
| User, role and organization management (FR-013–FR-020) | Partial | Eight canonical roles and administrator staff management exist. Phase 1 adds normalized roles, permissions, multi-role pivot, organization, department and office tables; full organization/office assignment UI and full permission matrix remain. |
| Farm registry (FR-021–FR-030) | Foundation implemented | Farm ID, farm type catalogue, owner association, GPS/location records, administrative-area linkage, duplicate owner/GPS verification and audit fields are supported. Edit/history workflows and authoritative administrative boundaries remain. |
| Farmer registry (FR-031–FR-040) | Foundation implemented | Personal details, contact and physical address, status, searchable Farmer ID/National ID/phone/name, duplicate checks, multiple farm associations and ownership history schema are supported. Farmer profile edit and complete ownership-history workflow remain. |
| Animal registry and identification (FR-041–FR-055) | Database foundation implemented | Animal can link to farm and farmer; species/breed catalogue, identification records, unique tag values, movement, ownership transfer, vaccination and disease-history tables are present. Full operational forms and traceability history workflows remain. |
| Disease reporting and investigation (FR-056 onwards) | Partial | Case reporting and investigation foundation exists. Individual animal/category/area links and symptom reference relationship are added. Full lifecycle, attachments and formal sign-off remain. |
| Sample collection and tracking | Workflow foundation implemented | Collection → packaging → dispatch → receipt → QC accept/reject → assignment → testing → independent review → release. Sample types, transportation, storage and custody data structures are added; approved catalogues and full operations UI remain. |
| Laboratory registry, tests and results | Database foundation implemented | Laboratory sections, staff assignments, test catalogue, methods, test assignments, results, equipment and QC records are represented. Existing result workflow remains; catalogue-driven test assignment, calibration and QC UI remain. |
| Outbreak monitoring | Partial | Outbreak register exists and case-link pivot is added. Full lifecycle and alert rules remain. |
| Notifications | Partial | In-app notifications exist. SMS/email delivery, configuration, retries and delivery tracking remain. |
| Dashboards, reports and analytics | Partial | Existing dashboard and CSV exports remain. Dashboard/report definition and generated-report tables are added; complete analytics, exports and scheduling remain. |
| GIS (FR-173–FR-180) | Basic foundation | Existing Leaflet map is present. Farm locations and hierarchical administrative area schema are added. Authoritative boundaries, PostGIS geometry/SRID, spatial analysis and GIS export remain. |
| UTAMBUZI integration (FR-181–FR-190) | Blocked by external dependency | Requires official API documentation, credentials, mappings, test environment and approval from the UTAMBUZI owner. |
| Audit trail (FR-191–FR-198) | Partial | Existing audit records plus farmer/farm/animal/master-data events. Full login, modification, deletion, report, synchronization and searchable audit coverage remain. |
| System administration/master data (FR-199–FR-208) | Partial foundation | Admin master-data screens support farm types, species, breeds, disease categories/symptoms, sample types, tests/methods and administrative areas. Laboratory, organization, notification, backup and system settings administration remain. |
| Database requirements (SRS Chapter 6) | Schema foundation implemented | Additive migrations, foreign keys, unique constraints, indexes, normalized master data, audit actor/status fields, ownership and traceability relationships are added. PostgreSQL 14+/PostGIS deployment validation, approved data loading, migration reconciliation and production security/backup tests remain. |
| Deployment / operations | Partial | Codespaces SQLite development supported. Production target is PostgreSQL 14+ with PostGIS; HTTPS, backup/restore, monitoring, UAT and formal acceptance remain. |

## Phase 1 — Database and master data

See [Phase 1 database and master data design](PHASE_1_DATABASE_MASTER_DATA.md) for the table inventory, ERD, seeded values, validation scope and Ministry data dependencies.

### Seeded reference values

- Eight canonical user roles from `config/idss.php`.
- Farm types explicitly named in SRS FR-026: Pastoral, Agro-pastoral, Commercial, Dairy, Ranch and Poultry.

Species, breeds, disease categories, symptoms, sample types, test methods, laboratories, organizational units and administrative boundaries are not preloaded with guessed values. They must be populated from approved Ministry reference data.

## Important constraints

- The local Codespaces environment may use SQLite for development and automated tests. The SRS production database target is PostgreSQL 14 or later with PostGIS.
- Existing records must be preserved. Use additive migrations and backups; do not use `migrate:fresh` on a database containing records.
- The SRS does not provide authoritative administrative-area codes/boundaries, catalogue values for species/breeds/diseases/sample types/test methods, or Ministry organization identifiers. These remain data-governance inputs.
- Offline collection and MFA are future enhancements in the project scope, not Phase 1 acceptance items.

## Minimum end-to-end acceptance walkthrough

1. Administrator provisions users and their roles/organization/office assignments.
2. Field officer registers a farmer and farm using approved reference data.
3. Field officer registers individually identifiable poultry linked to the farmer and farm.
4. Field officer reports and investigates a suspected disease case.
5. Field officer collects, packages and dispatches a sample with a traceable custody history.
6. Laboratory staff receive and assess the sample; the manager assigns the approved diagnostic tests.
7. Technician records results; an independent manager reviews and approves them.
8. System links the result to the case and makes it available for surveillance, outbreak monitoring and reporting.
9. Verify access control, audit records, GIS and external synchronization.
10. Conduct Ministry UAT, database migration reconciliation and production security review before rollout.
