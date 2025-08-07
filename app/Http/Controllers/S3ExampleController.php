<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\S3Service;
use App\Traits\HasS3Uploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class S3ExampleController extends Controller
{
    use HasS3Uploads;

    protected $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    /**
     * Upload a single file to S3
     */
    public function uploadSingle(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Validate file type
            if (!$this->validateFile($file, 'image')) {
                return response()->json([
                    'error' => $this->getFileValidationMessage('image')
                ], 400);
            }

            try {
                $path = $this->s3Service->uploadFile($file, 'uploads');
                $url = $this->s3Service->getFileUrl($path);

                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'data' => [
                        'path' => $path,
                        'url' => $url,
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload file',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No file provided'
        ], 400);
    }

    /**
     * Upload multiple files to S3
     */
    public function uploadMultiple(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // 10MB max per file
        ]);

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploadedFiles = [];

            foreach ($files as $file) {
                // Validate file type
                if (!$this->validateFile($file, 'image')) {
                    continue; // Skip invalid files
                }

                try {
                    $path = $this->s3Service->uploadFile($file, 'uploads');
                    $url = $this->s3Service->getFileUrl($path);

                    $uploadedFiles[] = [
                        'path' => $path,
                        'url' => $url,
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                } catch (\Exception $e) {
                    // Log error but continue with other files
                    Log::error('Failed to upload file: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($uploadedFiles) . ' files uploaded successfully',
                'data' => $uploadedFiles
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'No files provided'
        ], 400);
    }

    /**
     * Delete a file from S3
     */
    public function deleteFile(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        try {
            $deleted = $this->s3Service->deleteFile($path);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found or already deleted'
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        try {
            $exists = $this->s3Service->fileExists($path);

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $url = $this->s3Service->getFileUrl($path);
            $size = $this->s3Service->getFileSize($path);
            $metadata = $this->s3Service->getFileMetadata($path);

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $path,
                    'url' => $url,
                    'size' => $size,
                    'metadata' => $metadata,
                    'exists' => $exists,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List files in a folder
     */
    public function listFiles(Request $request)
    {
        $folder = $request->input('folder', 'uploads');

        try {
            $files = Storage::disk('s3')->files($folder);
            $fileList = [];

            foreach ($files as $file) {
                $fileList[] = [
                    'path' => $file,
                    'url' => $this->s3Service->getFileUrl($file),
                    'size' => $this->s3Service->getFileSize($file),
                    'filename' => basename($file),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'folder' => $folder,
                    'files' => $fileList,
                    'count' => count($fileList),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get S3 configuration information
     */
    public function getConfig()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
                'url' => config('filesystems.disks.s3.url'),
                'default_disk' => config('filesystems.default'),
                'allowed_types' => config('s3.allowed_types'),
                'max_file_sizes' => config('s3.max_file_size'),
                'folders' => config('s3.folders'),
            ]
        ]);
    }
}
