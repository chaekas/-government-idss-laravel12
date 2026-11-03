# Phase 3 — Laboratory Management

## Scope

This phase completes the laboratory diagnostic workflow around the existing sample chain of custody. It covers the approved diagnostic test catalogue, technician assignment, electronic result capture, independent review, quality-control inspections, equipment calibration and corrective/preventive actions.

## Delivered

### 1. Diagnostic test catalogue and assignment
- Managers assign one or more active catalogue tests to a received, quality-accepted sample and an active technician.
- Each assignment stores its laboratory, technician, assigning user, assignment time and workflow status.
- Technicians can submit results only against a test assignment belonging to their account.
- Result records retain the catalogue test, optional approved test method, assignment, technician, timestamps, observations, qualitative/numeric findings and reviewer comments.
- Free-text test names are no longer accepted for new technician result submissions; the assigned catalogue test supplies the official test name.

### 2. Independent review and repeat testing
- The technician who performed a test cannot approve their own result.
- Reviewers approve or return a result with mandatory comments.
- Rejected assignments can be reassigned as a new attempt; prior results and assignments remain in history.
- A sample is not finally released merely because one result was approved. The latest assignment for every diagnostic test on that sample must be approved.
- Final release writes the summary, updates the disease case through the surveillance workflow, records a sample movement, and notifies relevant stakeholders.

### 3. Laboratory sections and staff workflow
- Laboratory managers can register sections such as virology or bacteriology.
- Sample assignment links the technician and section to the sample and creates per-test assignment records.
- Technician result-entry forms list only open tests assigned to the signed-in technician.

### 4. Quality-control inspections
- Managers record QC type, laboratory, optional sample/test, date, outcome and findings.
- Failed inspections require a corrective action description and automatically create a tracked corrective/preventive action (CAPA) record.
- QC records remain searchable in the laboratory operations workspace.

### 5. Equipment and calibration
- Managers register equipment against a laboratory with equipment code, manufacturer/model, serial number and status.
- Calibration records preserve date, due date, outcome, certificate reference, findings and performer.
- Failed or conditional calibration moves equipment into maintenance and creates a CAPA record.
- Equipment calibration history remains available from the laboratory operations page.

### 6. Corrective and preventive action tracking
- CAPA records have unique action codes, laboratory, source QC/calibration, priority, assignee, due date and status.
- Managers can move actions through open, in-progress, resolved or cancelled.
- Resolution text and resolved timestamp are required/stored when closing an action.

### 7. Security and audit
- Laboratory operations management routes are restricted to laboratory managers, zonal laboratory administrators and system administrators.
- Technicians can submit assigned results but cannot manage equipment, QC controls or CAPA records.
- Creation, calibration, QC and CAPA status changes are written to the audit log.

## Automated acceptance tests

The test suite covers:
- Assignment of catalogue diagnostic tests to a sample and technician.
- Technician submission of a result linked to the assigned test and approved method.
- Independent manager review and final result release.
- Prevention of final release while another assigned test is still pending.
- Technician access restrictions for management controls.
- Equipment registration, calibration failure, maintenance status and CAPA generation.
- Failed QC inspection and CAPA generation.
- Corrective action resolution.

## Ministry / operational inputs still required

1. Approved national diagnostic test catalogue and specimen requirements.
2. Approved test methods, SOP identifiers and result interpretation criteria for each disease.
3. Laboratory section structure, active laboratory registry and staff-to-laboratory assignments.
4. Equipment inventory, calibration certificates, calibration intervals and approved service providers.
5. Laboratory quality manual, QC acceptance criteria, corrective-action owners and closure SLAs.
6. Approved electronic signature policy and retention rules for immutable final laboratory reports.

These values must come from the Ministry and accredited laboratories; the application does not invent clinical or quality standards.

## Phase 3 exit status

**The laboratory management software workflows and automated acceptance tests are implemented.** Final operational acceptance depends on loading the Ministry-approved diagnostic catalogue, SOPs, equipment inventory and QC/calibration standards, followed by laboratory stakeholder UAT.
