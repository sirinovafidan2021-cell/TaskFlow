# TaskFlow post-release roadmap

## Status and gate

This document is **not** part of the current implementation scope. Work here may start only after every task in `IMPLEMENTATION_PLAN.md` is verified and its Final Definition of Done passes.

The current implementation intentionally uses documented direct service calls between modules. It must not be delayed by an event bus, contract layer, microservices, speculative infrastructure, or advanced security controls from this roadmap.

Roadmap items do not repair incomplete core behavior. If a missing capability is required by `PROJECT_BRIEF.md`, it belongs in the implementation plan, not here.

## Entry criteria

Before any roadmap task is opened:

- all P0/P1 findings in `CURRENT_STATE_AUDIT.md` are closed;
- the complete minimal Jira-like product passes Pest, Playwright, manual Web/API, build, formatting, and baseline security gates;
- the direct dependency graph and cross-module call sites have been measured;
- production-like observability can show errors, request latency, queue failures, and storage failures;
- the proposed change has a written problem statement, owner, acceptance criteria, migration/rollback plan, and evidence target.

---

## Track R1 — Looser module coupling

### R1.1 — Measure dependency pressure

- Generate the actual module dependency graph from imports, providers, routes, service calls, policies, and tests.
- Identify high-change call sites and cycles; do not abstract stable calls merely because they cross a module boundary.
- Record baseline coupling metrics and the business failure modes that justify each later change.

**Exit:** refactoring candidates are prioritized from evidence, not preference.

### R1.2 — Define narrow public module contracts

- Publish read/query contracts only where multiple consumers need the same stable capability.
- Start with project membership/access, Media metadata/streaming, and Dashboard metric projections.
- Keep domain models, repositories, migrations, and internal DTOs private to their owner module.
- Add contract tests before migrating callers.

**Exit:** consumers depend on a small versioned surface without receiving another module's Eloquent models.

### R1.3 — Introduce domain events for completed facts

- Replace direct Activity and notification side-effect calls with past-tense domain events where asynchronous or multi-consumer delivery has clear value.
- Dispatch only after the owning transaction commits.
- Make listeners idempotent and observable; define retry and dead-letter handling before queues are enabled.
- Preserve synchronous authorization and core state transitions in the owning service.

**Exit:** side-effect consumers can fail/retry without corrupting the source transaction, and behavior remains equivalent.

### R1.4 — Extract read models where measurement supports it

- Consider Dashboard/activity projections only after query profiling shows direct aggregation is a real bottleneck.
- Define rebuild, consistency, replay, and backfill behavior.
- Keep the source of truth in domain-owned tables.

**Exit:** measured latency/resource targets improve and projections can be rebuilt safely.

### R1.5 — Boundary enforcement and resilience

- Add automated import/dependency rules for public contracts.
- Add event/contract schema compatibility tests.
- Document timeout, retry, circuit-breaker, and degraded-mode behavior only for calls that actually become remote or asynchronous.
- Reassess modular monolith versus service extraction; do not split deployment units without an independent scaling, ownership, or reliability need.

**Exit:** the dependency graph is simpler than the implementation baseline and operational complexity has not increased without a measured benefit.

---

## Track R2 — Advanced security and privacy

Baseline security in `SECURITY.md` is mandatory implementation work. This track begins only after that baseline passes.

### R2.1 — Stronger account and session protection

- Optional/required MFA policy for privileged accounts.
- Session/device inventory, remote sign-out, re-authentication for sensitive operations, and configurable idle/absolute timeouts.
- Suspicious-login detection and alerting with privacy-safe metadata.

### R2.2 — Mature personal access token lifecycle

- Token expiration, rotation, device/name metadata, last-used visibility, revoke-all, and admin policy limits.
- Step-up authentication before high-impact token operations.
- Anomaly alerts for impossible or abusive token usage.

### R2.3 — Media defense in depth

- Quarantine state and asynchronous malware scanning.
- Image/document content sanitization where supported.
- Scan-engine outage policy, timeout/retry rules, and a safe administrator review path.
- Storage encryption/key rotation and retention/legal-hold policy if business requirements demand them.

### R2.4 — Browser and application hardening

- Content Security Policy deployed in report-only mode before enforcement.
- HSTS and a reviewed security-header policy at the correct proxy/application layer.
- Automated dependency, container, secret, and static analysis checks with triage ownership.
- Abuse detection and adaptive rate limits based on measured traffic.

### R2.5 — Audit, privacy, and incident readiness

- Tamper-evident audit retention, privileged-action review, and export/access controls.
- Data retention/deletion policy, privacy export, and redaction strategy.
- Security logging/alert routing, incident playbooks, evidence preservation, and regular recovery exercises.

**Exit for R2:** each control has a threat it mitigates, an owner, a test, an operational runbook, and no unresolved usability/recovery regression.

---

## Track R3 — Evidence-driven operations and performance

This is not a feature backlog.

- Define service-level indicators for error rate, p95 latency, database pressure, queue health, and Media storage/stream failures.
- Profile before adding caches or denormalized projections.
- Add cache ownership, invalidation, stampede protection, and fallback rules before caching domain data.
- Automate encrypted backups and perform restore drills against documented recovery objectives.
- Establish zero/low-downtime migration patterns only when deployment requirements justify them.

## Explicitly outside this roadmap

The following are new product initiatives and need separate discovery/decision records rather than being smuggled into technical roadmap work:

- workspaces or multi-tenant organizations;
- sprints, releases, epics, roadmaps, or story points;
- custom workflows, custom fields, components, or generic categories;
- multi-assignee work items;
- arbitrary-depth issue hierarchy or dependencies;
- recurring tasks, automation rules, integrations, or public webhooks;
- external customer portal, billing, or mobile applications.

## Roadmap governance

For each accepted roadmap item:

1. create a decision record describing the measured problem and rejected alternatives;
2. split it into independently reversible tasks;
3. record before/after dependency, performance, security, and failure-recovery evidence;
4. update Architecture, Security, Test Strategy, and API documents only after behavior is verified;
5. keep core product rules unchanged unless a separate product decision explicitly changes them.
