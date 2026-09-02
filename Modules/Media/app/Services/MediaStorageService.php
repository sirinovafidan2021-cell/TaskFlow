<?php

namespace Modules\Media\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Data\MediaMetadataData;
use Modules\Media\Exceptions\MediaStorageException;
use Modules\Media\Exceptions\MediaUploadValidationException;
use Modules\Media\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MediaStorageService
{
    /** @var array<string, array<int, string>> */
    private const ALLOWED_TYPES = [
        'pdf' => ['application/pdf'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'webp' => ['image/webp'],
        'txt' => ['text/plain'],
        'log' => ['text/plain'],
        'md' => ['text/plain'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ];

    public function __construct(private readonly MediaMetadataService $metadata) {}

    /**
     * Validates every item before creating either a database record or a physical file.
     *
     * @param array<int, UploadedFile> $files
     * @return array<int, Media>
     */
    public function storeMany(User $uploader, array $files): array
    {
        if ($files === [] || count($files) > config('media.max_files')) {
            throw new MediaUploadValidationException('The number of uploaded files is not allowed.');
        }

        $prepared = array_map(fn (UploadedFile $file): array => $this->prepare($file), $files);
        $disk = (string) config('media.disk');
        $storedPaths = [];

        try {
            $metadata = [];

            foreach ($prepared as $item) {
                $uuid = (string) Str::uuid();
                $path = 'media/'.$uuid.'.'.$item['extension'];
                $stored = Storage::disk($disk)->putFileAs('media', $item['file'], basename($path));

                if ($stored === false) {
                    throw new MediaStorageException('The uploaded file could not be stored.');
                }

                $storedPaths[] = $path;
                $metadata[] = new MediaMetadataData(
                    uuid: $uuid,
                    disk: $disk,
                    path: $path,
                    originalName: $item['original_name'],
                    extension: $item['extension'],
                    mimeType: $item['mime_type'],
                    size: $item['size'],
                    sha256: $item['sha256'],
                    imageWidth: $item['image_width'],
                    imageHeight: $item['image_height'],
                );
            }

            return $this->metadata->registerMany($uploader, $metadata);
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                try {
                    Storage::disk($disk)->delete($path);
                } catch (Throwable) {
                    // The original persistence failure remains the useful error for the caller.
                }
            }

            if ($exception instanceof MediaUploadValidationException || $exception instanceof MediaStorageException) {
                throw $exception;
            }

            throw new MediaStorageException('The uploaded file metadata could not be saved.', previous: $exception);
        }
    }

    public function store(User $uploader, UploadedFile $file): Media
    {
        return $this->storeMany($uploader, [$file])[0];
    }

    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);

        try {
            $this->metadata->delete($media);
        } catch (Throwable $exception) {
            throw new MediaStorageException('The media metadata could not be deleted.', previous: $exception);
        }

        try {
            if (! $disk->delete($media->path)) {
                throw new MediaStorageException('The stored media file could not be deleted.');
            }
        } catch (Throwable $exception) {
            if ($exception instanceof MediaStorageException) {
                throw $exception;
            }

            throw new MediaStorageException('The stored media file could not be deleted.', previous: $exception);
        }
    }

    public function download(Media $media): StreamedResponse
    {
        return $this->stream($media, false);
    }

    public function preview(Media $media): StreamedResponse
    {
        if (! $this->isPreviewable($media)) {
            return $this->download($media);
        }

        return $this->stream($media, true);
    }

    private function stream(Media $media, bool $inline): StreamedResponse
    {
        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            throw new MediaStorageException('The stored media file is unavailable.');
        }

        return $disk->response($media->path, $this->safeFilename($media), [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], $inline ? 'inline' : 'attachment');
    }

    /** @return array{file: UploadedFile, extension: string, mime_type: string, original_name: string, size: int, sha256: string, image_width: ?int, image_height: ?int} */
    private function prepare(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new MediaUploadValidationException('The uploaded file is invalid.');
        }

        $extension = strtolower((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        $mimeType = $this->detectedMimeType($file);
        $size = (int) $file->getSize();

        if (! isset(self::ALLOWED_TYPES[$extension]) || ! in_array($mimeType, self::ALLOWED_TYPES[$extension], true)) {
            throw new MediaUploadValidationException('The file type is not allowed.');
        }

        if ($size < 1 || $size > config('media.max_file_size')) {
            throw new MediaUploadValidationException('The file size is not allowed.');
        }

        [$width, $height] = $this->imageDimensions($file, $mimeType);
        $path = $file->getRealPath();

        if ($path === false || ! is_file($path)) {
            throw new MediaUploadValidationException('The uploaded file is unavailable.');
        }

        return [
            'file' => $file,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'original_name' => $this->safeOriginalName($file->getClientOriginalName(), $extension),
            'size' => $size,
            'sha256' => hash_file('sha256', $path),
            'image_width' => $width,
            'image_height' => $height,
        ];
    }

    /** @return array{?int, ?int} */
    private function imageDimensions(UploadedFile $file, string $mimeType): array
    {
        if (! str_starts_with($mimeType, 'image/')) {
            return [null, null];
        }

        $dimensions = @getimagesize((string) $file->getRealPath());
        $width = $dimensions[0] ?? null;
        $height = $dimensions[1] ?? null;

        if (! is_int($width) || ! is_int($height)
            || $width < 1 || $height < 1
            || $width > config('media.max_image_width')
            || $height > config('media.max_image_height')
            || $width * $height > config('media.max_image_pixels')) {
            throw new MediaUploadValidationException('The image dimensions are not allowed.');
        }

        return [$width, $height];
    }

    private function safeOriginalName(string $name, string $extension): string
    {
        $name = preg_split('/[\r\n]/', $name)[0] ?? '';
        $name = basename(str_replace('\\', '/', str_replace("\0", '', $name)));
        $name = trim((string) preg_replace('/[^A-Za-z0-9._ -]/', '', $name));

        return $name !== '' ? $name : 'download.'.$extension;
    }

    private function safeFilename(Media $media): string
    {
        return $this->safeOriginalName($media->original_name, $media->extension);
    }

    private function isPreviewable(Media $media): bool
    {
        return str_starts_with($media->mime_type, 'image/') || $media->mime_type === 'application/pdf';
    }

    private function detectedMimeType(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_file($path)) {
            throw new MediaUploadValidationException('The uploaded file is unavailable.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo === false ? false : finfo_file($finfo, $path);

        if ($finfo !== false) {
            finfo_close($finfo);
        }

        if (! is_string($mimeType) || $mimeType === '') {
            throw new MediaUploadValidationException('The uploaded file type could not be detected.');
        }

        return $mimeType;
    }
}
