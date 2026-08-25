# Security baseline and threat model

Security controls in this document are implementation requirements unless explicitly marked roadmap.

## Protected assets

- user credentials, sessions, personal access tokens, and role assignments;
- project membership and inaccessible project/work-item metadata;
- comments, activity payloads, notifications, and audit actor/context;
- private media content and internal storage metadata;
- API response contracts and rate-limit identity;
- database integrity for project keys, issue numbers, assignments, labels, watchers, and parent relationships.

## Primary threats

- IDOR by changing project/task/comment/media/user/label IDs;
- token ability treated as authorization;
- cross-project filter-option or activity metadata leakage;
- suspended/removed users retaining access;
- archived/completed project mutation through an alternate entry point;
- malicious upload, MIME spoofing, path traversal, filename/header injection, or public media URL exposure;
- XSS through descriptions/comments/filenames/activity summaries;
- CSRF on Web mutations;
- credential/token leakage in logs/activity/API/DOM/local storage;
- mass assignment and raw sort/filter injection;
- race conditions in issue numbering/ranking/member removal;
- broad exception handling that exposes messages or hides programming faults.

## Authentication

- Web login is session-based, rate-limited by normalized email plus IP, and regenerates the session on success.
- Logout invalidates the session and regenerates the CSRF token.
- Public registration is absent.
- Suspended users cannot log in. Suspension is never blocked by assignments: it revokes all PATs/sessions, unassigns open work, removes watcher subscriptions, and records sanitized Activity while preserving historical references.
- Password input is never logged; password rules use Laravel's password validation and stored values use hashing casts/services.
- Admin password reset requires admin policy and invalidates all target-user sessions/PATs. Self-service password change requires current-password confirmation, invalidates other sessions/all PATs, and regenerates the current session.
- API token issuance is separately rate-limited and returns a generic credential error.

## Sanctum tokens

- Plaintext token is returned only once.
- Token hash, authorization header, and plaintext token are never placed in Resources, Activity, notifications, exceptions, logs, browser local/session storage, or permanent DOM.
- Requested abilities are validated against the canonical enum and may be narrowed by product policy.
- `auth:sanctum` plus route ability plus Spatie permission plus Policy/Gate are all required where applicable.
- Revoke invalidates the current token immediately.

Advanced expiry/rotation UI and token anomaly monitoring are roadmap security items.

## Authorization and data isolation

- All record routes call explicit policies.
- List/filter-option queries start from actor-visible project membership scope.
- Nested child records are resolved/scoped under their parent and mismatches return 404.
- Project membership grants browse access; manager/reporter/assignee/author/uploader status determines mutation authority.
- Account/global role and project role combinations are covered by a dataset-driven matrix.
- A member removed from a project loses browse/API/watcher access immediately.
- A suspended user cannot remain an active assignee or watcher recipient.
- An Archived or Completed project rejects mutation through Web, API, Livewire, direct service tests, and board/JavaScript endpoints.

## Input and output safety

- HTTP input uses Form Requests; Livewire actions use equivalent validation.
- DTOs are built only from validated values.
- Sort fields/directions and enums use allowlists.
- Pagination is bounded to 1–100.
- Blade escapes user text; plain-text newlines are rendered without raw HTML.
- API Resources explicitly list fields and never serialize raw models.
- Error messages are user-safe and do not expose SQL, file paths, stack traces, or record existence outside authorized scope.

## Media security

All requirements in `MEDIA.md` are mandatory. In particular:

- private storage and authorized streaming only;
- random paths and sanitized download names;
- server-side MIME/content verification;
- no SVG/executable/unknown types;
- per-file and per-request limits;
- image dimension limits;
- no disk/path/checksum leakage;
- consistent database/file deletion with failure tests.

Malware scanning and quarantine are roadmap controls, not an excuse to weaken the current allowlist.

## Audit and notification safety

- Activity uses a canonical event enum and safe schema.
- Update events include approved old/new values, not arbitrary model dumps.
- A recursive sensitive-key denylist removes password, token, secret, authorization, cookie, path, and binary values.
- Notifications store safe identifiers/summaries only and re-authorize the linked page when opened.
- User/admin/token/media security events are tested for secret absence.

## Database integrity

- Project key, slug, project membership, issue display number/sequence, label name/slug, watcher membership, task/media association, and parent relationships have database constraints/indexes.
- Concurrent issue allocation and backlog reorder use transactions/locking and dedicated tests.
- Foreign-key delete behavior is explicit. User/project/task historical references are not silently destroyed.
- All migrations are reversible and tested with SQLite; selected compatibility checks run on MySQL with explicit approval.

## Web security

- All Web mutations use CSRF protection.
- Authorization does not rely on hidden buttons.
- Session cookies follow environment-appropriate secure/httpOnly/sameSite configuration.
- File responses use `nosniff` and safe disposition.
- No sensitive value is written to browser console or local storage.
- Basic security headers are reviewed during the stabilization gate; strict CSP rollout is roadmap security work unless the current UI can adopt it without unsafe exceptions.

## Rate limiting

Separate named limits cover Web login, API token issuance, normal API traffic, media upload, and high-cost search where appropriate. Tests prove 429 behavior without stress-testing production.

## Security acceptance

The release gate requires:

- role x membership x ability x record policy tests;
- cross-project/nested ID tampering tests;
- suspended-user/open-assignment/watcher cleanup, password reset/change, session invalidation, and revoked-token tests;
- archived/completed mutation parity tests;
- media spoof/size/dimension/path/ownership and multi-file all-or-nothing failure tests;
- activity/resource/notification secret-absence tests;
- CSRF, XSS escaping, safe errors, rate limits, mass-assignment, and sort-whitelist review;
- no open Critical or High findings.

## Roadmap security

2FA, malware scanning/quarantine, periodic access review, CSP enforcement, secret rotation operations, advanced token lifecycle, security-event alerting, dependency/container scanning automation, retention/privacy policies, and incident response drills are defined only in `ROADMAP.md`.
