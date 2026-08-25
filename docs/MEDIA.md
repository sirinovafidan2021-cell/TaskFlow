# Central Media module specification

## Decision

All uploaded files and images are centralized in `Modules/Media`. Tasks no longer owns physical disk/path metadata. Tasks owns only the authorized association between a Task and a Media record.

The central module exists to prevent every future domain from implementing storage, MIME validation, preview, cleanup, and security differently.

## Target schema

### `media`

Owned by Media:

- `id`
- `uuid` unique public-safe identifier
- `uploaded_by` foreign key to users
- `disk`
- `path` unique randomized private path
- `original_name`
- `extension`
- `mime_type` detected server-side
- `size`
- `sha256`
- `image_width` nullable
- `image_height` nullable
- timestamps and soft delete

Disk, path, and checksum are internal and never emitted by normal API Resources.

### `task_attachments`

Owned by Tasks as an association:

- `id`
- `task_id` foreign key
- `media_id` foreign key and unique for single ownership in the current scope
- timestamps
- indexes on task/date and media

Do not use `mediable_type`/`mediable_id` in the current design. A later consumer creates its own association table.

## Upload flow

```text
Task Web/API controller
  -> authorize upload on Task
  -> validate request count/size/basic shape
  -> TaskMediaService
       -> MediaService stores and detects metadata
       -> Tasks repository creates task_attachments association
       -> ActivityRecorder records safe event
       -> compensate Media if association/activity transaction fails
```

The Media module validates server-detected content, not only client extension/MIME. Random UUID-based storage paths prevent filename/path guessing. Original names are display/download metadata only and are sanitized for response headers.

For a multi-file request, validate every item before storing any file. The request is all-or-nothing: if validation, storage, association, or Activity fails for one item, reject the request and compensate every Media record, association, and physical file created by that request.

## Initial allowlist

| Category | MIME/extensions |
| --- | --- |
| PDF | `application/pdf`, `.pdf` |
| Images | PNG, JPEG, WebP |
| Text | `text/plain`, `.txt`, `.log`, `.md` |
| Word | DOC, DOCX |
| Excel | XLS, XLSX |

SVG, HTML, JavaScript, archives, scripts, executables, and unknown binary types are rejected. Extension and detected MIME must be an accepted pair.

Limits:

- maximum 10 MB per file;
- maximum 5 files per request;
- safe image dimension/pixel limits to prevent decompression abuse;
- configurable limits with tests locking the accepted baseline.

## Preview and download

- Storage is private; there are no public permanent URLs.
- The parent Task is authorized before association lookup/streaming.
- A media identifier under the wrong Task returns 404.
- Images and PDFs may be streamed inline with safe `Content-Type`, `Content-Disposition`, `X-Content-Type-Options: nosniff`, and cache policy.
- Other files use attachment disposition.
- Filenames are encoded safely; CR/LF and path separators are removed.
- HTTP Range/206 responses are not part of v1; authorized preview/download returns the complete response. Range support requires a later explicit contract decision and tests.

## Delete lifecycle

Uploader may detach their own attachment while the project is Active; project managers may detach any task attachment. Completed/Archived projects are read-only.

Deletion sequence must tolerate partial failures:

1. authorize Task and nested association;
2. remove association and record activity transactionally;
3. delete/soft-delete Media after commit when no associations remain;
4. record/report physical-delete failure for retry instead of restoring an inconsistent association silently.

An orphan reconciliation command/job is roadmap security/operations work; implementation must still include deterministic compensation for request-time failures.

## Migration from inherited attachments

The inherited `task_attachments` contains task and physical metadata together. The safe migration plan is:

1. create Media module/table;
2. add nullable `media_id` to inherited task attachments;
3. backfill one Media record per attachment without moving physical files initially;
4. verify counts, paths, sizes, nested access, and downloads;
5. make `media_id` non-null/unique;
6. remove duplicated physical metadata only in a separately approved migration;
7. switch services/resources to Media ownership;
8. add rollback/compensation tests.

If the user proves the database is disposable pre-production data, the plan may instead create a clean final schema; that is an explicit decision, never an assumption.

## Activity and privacy

Media activity may contain media UUID/id, safe original name, size, MIME, task/project IDs, and actor. It must never contain disk, path, checksum, file content, temporary path, authorization headers, or signed URL data.

## Future security (roadmap only)

Malware scanning/quarantine, thumbnails/variants, retention policies, content-disarm, object-storage lifecycle, and asynchronous orphan reconciliation remain advanced security roadmap work. Do not pre-implement them during the current plan.
