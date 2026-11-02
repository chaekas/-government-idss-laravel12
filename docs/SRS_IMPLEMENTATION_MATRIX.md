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
| Sample collection and tracking | Workflow foundation implemented | Generated Sample ID/barcode and lab reference; collection → packaging → dispatch/in transit → laboratory receipt → QC accept/reject → lab section/technician assignment → examination start → test submission → independent manager review → final result release. Custody movements, audit records, and in-app notifications are recorded. Printable barcode labels, signed custody receipts, and region/lab-level scoping still need completion. |
| Laboratory registry, tests and results | Workflow foundation implemented | Assigned technicians can record findings; laboratory managers independently review; rejected results return for correction/repeat; final sample release waits until the latest recorded result for each test is approved. Add master test catalogue, result attachments, formal QC records, equipment/reagent records, electronic signatures and immutable report documents. |
| Outbreak monitoring | Foundation implemented | Outbreak register with location, counts, severity and response notes. Add linkage to cases, alert thresholds and response lifecycle. |
| Notifications | Partial | In-app notifications are generated for outbreak records, sample arrival/quality events, and laboratory result review. Approved SMS/email delivery is not configured. |
| Dashboards and reports | Partial | Counts and recent cases shown. Add full surveillance/laboratory indicators, filters, PDF/Excel exports and scheduled reports. |
| GIS | Not implemented | Add interactive map, authorized geographic filtering and hotspot analysis using validated coordinates. |
| UTAMBUZI | Blocked by external dependency | Requires official API documentation, credentials, data mappings, test environment and approval from the system owner. |
| Audit trail | Partial | Audit records exist for key new workflows and user administration. Extend to every sensitive action and provide a protected audit report. |
| Deployment / operations | Partial | Codespaces SQLite development supported. Production target requires PostgreSQL, HTTPS, backups, monitoring, UAT and formal acceptance. |

## Minimum end-to-end acceptance walkthrough

1. Administrator creates accounts for a field officer, laboratory technician, and laboratory manager.
2. Field officer registers a farmer and farm, then registers poultry/animals.
3. Field officer reports a suspected disease case and records an investigation.
4. Field officer registers, packages, and dispatches the sample; the system records each chain-of-custody event.
5. Laboratory staff record receipt and sample condition, then accept or reject the sample through quality control.
6. Laboratory manager assigns the accepted sample to a laboratory section and an active technician.
7. The assigned technician starts examination, records one or more tests, and submits findings for independent review.
8. Laboratory manager approves or returns each result with comments; a returned result can be repeated and resubmitted.
9. Once the latest recorded result for every test is approved, the system releases the final result, updates case status, records the release in the custody timeline, and notifies field, laboratory, regional, zonal and Ministry stakeholders.
10. Verify role restrictions, audit trail, and that pending/unapproved results are not represented as final diagnoses.
11. Conduct stakeholder UAT and security review before production use.
