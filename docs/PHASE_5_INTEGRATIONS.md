# Phase 5 — Government and Messaging Integrations

## Scope

This phase establishes a safe integration boundary for UTAMBUZI, SMS, email and other approved government systems. The application must not guess third-party API endpoints, request formats, authentication schemes or government data-sharing permissions.

## Delivered

### 1. Outbound integration outbox
- Outbound events are stored in a persistent queue with provider, channel, event type, recipient, payload, status, attempt count, retry time and provider reference.
- The outbox supports queued, processing, sent, failed and blocked states.
- Failed deliveries use bounded exponential retry delays.
- Delivery attempts are recorded in a separate integration audit log.

### 2. Adapter contract
- Providers plug into the OutboundIntegrationAdapter contract.
- An adapter must implement the provider's officially approved API and authentication contract.
- Provider delivery is disabled by default.
- A provider cannot send unless the corresponding environment flag is explicitly enabled and a valid adapter class is configured.
- The integration administration page shows provider enablement, adapter configuration, queue status and delivery logs.

### 3. Email
- A Laravel Mail adapter is provided.
- Email delivery is disabled by default and requires the deployment's approved SMTP/API mail configuration and explicit enablement.

### 4. SMS
- SMS uses a provider-specific adapter supplied after the Ministry selects and approves its SMS gateway.
- No gateway URL, request schema, sender ID or credential is assumed by the application.

### 5. UTAMBUZI and other government systems
- UTAMBUZI events can be queued through the same adapter contract.
- No endpoint, payload mapping, authentication method or data-sharing permission is invented.
- A government-system adapter can be installed when the official API specification and sandbox credentials are supplied.

### 6. Scheduling and administration
- A scheduled worker command processes pending integration events.
- System administrators can inspect provider configuration, outbox status and audit logs from the Government Integrations page.
- Provider processing can be run manually for demonstration or by the Laravel scheduler.

## Automated acceptance tests

The test suite covers:
- Disabled UTAMBUZI provider is blocked without a network request.
- Blocked deliveries are written to the integration audit log.
- An explicitly enabled test adapter processes an event and records its provider reference.
- Adapter delivery failures are retried and logged.

## External dependencies blocking live connection

The following must be supplied by the Ministry/system owners before live enablement:
1. UTAMBUZI API specification, approved sandbox, credentials, authentication method, event endpoints, field mapping, rate limits and data-sharing approval.
2. Approved SMS provider contract, sender ID, API documentation, credentials, delivery receipts and opt-in/consent policy.
3. Approved SMTP or email API configuration, sender identity, templates, bounce handling and recipient policy.
4. Government information-security review and signed-off retention/audit requirements.

## Phase 5 exit status

**Integration framework, outbox, adapter contract, disabled-by-default safety controls, retry logging and administration UI are implemented.** Live UTAMBUZI, SMS and email delivery remain blocked until the external approvals, API contracts and credentials above are provided. This is an intentional security boundary, not a claim of live integration.
