<?php

namespace App\Traits;

use App\Services\S3Service;
use Illuminate\Http\UploadedFile;

trait HasS3Uploads
{
    /**
     * Upload a single file to S3
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string
     */
    protected function uploadToS3(UploadedFile $file, string $folder = 'uploads'): string
    {
        $s3Service = app(S3Service::class);
        return $s3Service->uploadFile($file, $folder);
    }

    /**
     * Upload multiple files to S3
     *
     * @param array $files
     * @param string $folder
     * @return array
     */
    protected function uploadMultipleToS3(array $files, string $folder = 'uploads'): array
    {
        $s3Service = app(S3Service::class);
        return $s3Service->uploadMultipleFiles($files, $folder);
    }

    /**
     * Delete a file from S3
     *
     * @param string $path
     * @return bool
     */
    protected function deleteFromS3(string $path): bool
    {
        $s3Service = app(S3Service::class);
        return $s3Service->deleteFile($path);
    }

    /**
     * Delete multiple files from S3
     *
     * @param array $paths
     * @return bool
     */
    protected function deleteMultipleFromS3(array $paths): bool
    {
        $s3Service = app(S3Service::class);
        return $s3Service->deleteMultipleFiles($paths);
    }

    /**
     * Get the full URL for a file stored in S3
     *
     * @param string $path
     * @return string
     */
    protected function getS3Url(string $path): string
    {
        $s3Service = app(S3Service::class);
        return $s3Service->getFileUrl($path);
    }

    /**
     * Check if a file exists in S3
     *
     * @param string $path
     * @return bool
     */
    protected function fileExistsInS3(string $path): bool
    {
        $s3Service = app(S3Service::class);
        return $s3Service->fileExists($path);
    }

    /**
     * Validate file type and size
     *
     * @param UploadedFile $file
     * @param string $type
     * @return bool
     */
    protected function validateFile(UploadedFile $file, string $type = 'image'): bool
    {
        $allowedTypes = config('s3.allowed_types.' . $type, []);
        $maxSize = config('s3.max_file_size.' . $type, 5 * 1024 * 1024);

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedTypes)) {
            return false;
        }

        if ($file->getSize() > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * Get file validation error message
     *
     * @param string $type
     * @return string
     */
    protected function getFileValidationMessage(string $type = 'image'): string
    {
        $allowedTypes = config('s3.allowed_types.' . $type, []);
        $maxSize = config('s3.max_file_size.' . $type, 5 * 1024 * 1024);

        $maxSizeMB = round($maxSize / (1024 * 1024), 1);

        return "File must be one of: " . implode(', ', $allowedTypes) . " and size must not exceed {$maxSizeMB}MB.";
    }
}
