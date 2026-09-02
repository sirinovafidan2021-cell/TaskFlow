<?php

namespace Modules\Tasks\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskAttachmentMediaBackfill
{
    public static function run(): void
    {
        DB::table('task_attachments')->whereNull('media_id')->orderBy('id')->each(function (object $attachment): void {
            $existing = DB::table('media')->where('path', $attachment->path)->first();
            $mediaId = $existing?->id;

            if ($mediaId === null) {
                $mediaId = DB::table('media')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'uploaded_by' => $attachment->uploaded_by,
                    'disk' => $attachment->disk,
                    'path' => $attachment->path,
                    'original_name' => $attachment->original_name,
                    'extension' => strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) ?: 'bin',
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->size,
                    'sha256' => self::checksumFor($attachment->disk, $attachment->path),
                    'created_at' => $attachment->created_at,
                    'updated_at' => $attachment->updated_at,
                ]);
            }

            DB::table('task_attachments')->where('id', $attachment->id)->update(['media_id' => $mediaId]);
        });
    }

    private static function checksumFor(string $disk, string $path): string
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                return hash('sha256', Storage::disk($disk)->get($path));
            }
        } catch (\Throwable) {
            // A missing preserved legacy file keeps a deterministic marker for later reconciliation.
        }

        return hash('sha256', 'missing:'.$disk.'|'.$path);
    }
}
