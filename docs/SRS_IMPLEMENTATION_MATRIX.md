# IDSS SRS Implementation Matrix

This matrix tracks the current Laravel repository against the supplied Software Requirements Specification. “Implemented foundation” means a usable initial workflow exists; it does not mean the requirement has passed user acceptance testing or production security review.

| SRS capability | Current state | Notes / acceptance evidence still needed |
|---|---|---|
| Authentication and sessions | Partial | Sign-in/out and login throttling exist. Add account lockout, reset delivery, idle timeout, and security review. |
| Role-based access control | Partial | Administrator-only user management and result approval checks exist. Complete and test the entire role-by-module matrix and geographic scoping. |
| User management | Foundation implemented | Administrator can create/update/deactivate users and assign roles. Verify password reset policy and prevent unauthorized privilege escalation. |
| Farmer management | Foundation implemented | Registration/listing and generated farmer code. Add edit/history, duplicate checks, and national phone validation. |
| Farm management | Foundation implemented | Registration/listing and location coordinates. Add edit workflow, duplicate detection, full location hierarchy and audit coverage. |
| Poultry/animal registry | Foundation implemented | Animal ID, farm link, species and external ID. Add batch/flock inventory, ownership history and UTAMBUZI identifier mapping. |
| Disease reporting | Foundation implemented | Validated case reporting and listing. Add complete lifecycle transitions and case attachments. |
| Disease investigation | Foundation implemented | Assignment, field observations, findings, recommendations and coordinates. Add investigation approval/sign-off and role-based assignment rules. |
| Sample collection and tracking | Foundation implemented | Generated sample code/barcode value, collection, movement history, transport and quality statuses. Add printable barcode labels and formal chain-of-custody sign-off. |
| Laboratory tests and results | Foundation implemented | Result entry, review and approval workflow. Add test catalogue, repeat tests, attachments, QC, equipment/reagent records and immutable approved reports. |
| Outbreak monitoring | Foundation implemented | Outbreak register with location, counts, severity and response notes. Add linkage to cases, alert thresholds and response lifecycle. |
| Notifications | Not integrated | Add in-app notifications and configure approved SMS/email providers. |
| Dashboards and reports | Partial | Counts and recent cases shown. Add full surveillance/laboratory indicators, filters, PDF/Excel exports and scheduled reports. |
| GIS | Not implemented | Add interactive map, authorized geographic filtering and hotspot analysis using validated coordinates. |
| UTAMBUZI | Blocked by external dependency | Requires official API documentation, credentials, data mappings, test environment and approval from the system owner. |
| Audit trail | Partial | Audit records exist for key new workflows and user administration. Extend to every sensitive action and provide a protected audit report. |
| Deployment / operations | Partial | Codespaces SQLite development supported. Production target requires PostgreSQL, HTTPS, backups, monitoring, UAT and formal acceptance. |

## Minimum end-to-end acceptance walkthrough

1. Administrator creates accounts for a field officer, laboratory technician, and laboratory manager.
2. Field officer registers a farmer and farm, then registers poultry/animals.
3. Field officer reports a suspected disease case and records an investigation.
4. Field officer registers a sample, dispatches it, and records laboratory receipt and quality acceptance.
5. Laboratory technician records a test result and submits it for review.
6. Laboratory manager approves or rejects the result with review comments.
7. Dashboard shows updated surveillance counts and the audit trail records the actions.
8. Verify that a non-administrator cannot manage accounts and a non-laboratory-manager cannot approve results.
9. Verify that unapproved results are not represented as official confirmed diagnoses.
10. Conduct stakeholder UAT and security review before production use.
