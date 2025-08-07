<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3Service
{
    /**
     * Upload a file to S3
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $folder = 'uploads'): string
    {
        $fileName = $this->generateFileName($file);
        $path = $folder . '/' . $fileName;

        Storage::disk('s3')->put($path, file_get_contents($file), 'public');

        return $path;
    }

    /**
     * Upload multiple files to S3
     *
     * @param array $files
     * @param string $folder
     * @return array
     */
    public function uploadMultipleFiles(array $files, string $folder = 'uploads'): array
    {
        $uploadedPaths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedPaths[] = $this->uploadFile($file, $folder);
            }
        }

        return $uploadedPaths;
    }

    /**
     * Delete a file from S3
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->delete($path);
        }

        return false;
    }

    /**
     * Delete multiple files from S3
     *
     * @param array $paths
     * @return bool
     */
    public function deleteMultipleFiles(array $paths): bool
    {
        $deleted = true;

        foreach ($paths as $path) {
            if (!$this->deleteFile($path)) {
                $deleted = false;
            }
        }

        return $deleted;
    }

    /**
     * Get the full URL for a file stored in S3
     *
     * @param string $path
     * @return string
     */
    public function getFileUrl(string $path): string
    {
        return Storage::disk('s3')->url($path);
    }

    /**
     * Check if a file exists in S3
     *
     * @param string $path
     * @return bool
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }

    /**
     * Generate a unique filename for the uploaded file
     *
     * @param UploadedFile $file
     * @return string
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $timestamp = now()->timestamp;
        $randomString = Str::random(10);

        return $originalName . '_' . $timestamp . '_' . $randomString . '.' . $extension;
    }

    /**
     * Get file size from S3
     *
     * @param string $path
     * @return int|null
     */
    public function getFileSize(string $path): ?int
    {
        try {
            return Storage::disk('s3')->size($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get file metadata from S3
     *
     * @param string $path
     * @return array|null
     */
    public function getFileMetadata(string $path): ?array
    {
        try {
            return Storage::disk('s3')->getMetadata($path);
        } catch (\Exception $e) {
            return null;
        }
    }
}
