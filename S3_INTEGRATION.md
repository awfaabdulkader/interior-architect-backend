# AWS S3 Integration Guide

This guide explains how to set up and use AWS S3 integration in your Laravel project.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [Testing](#testing)
6. [Troubleshooting](#troubleshooting)

## Prerequisites

Before setting up S3 integration, you need:

1. **AWS Account**: Create an AWS account if you don't have one
2. **S3 Bucket**: Create an S3 bucket in your AWS account
3. **IAM User**: Create an IAM user with S3 permissions
4. **Access Keys**: Generate access keys for the IAM user

### Required IAM Permissions

Your IAM user needs the following permissions for the S3 bucket:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name",
                "arn:aws:s3:::your-bucket-name/*"
            ]
        }
    ]
}
```

## Installation

The S3 integration is already installed in this project. The following packages are included:

- `league/flysystem-aws-s3-v3`: AWS S3 adapter for Flysystem
- `aws/aws-sdk-php`: AWS SDK for PHP

## Configuration

### 1. Environment Variables

Add the following variables to your `.env` file:

```env
# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_aws_access_key_id
AWS_SECRET_ACCESS_KEY=your_aws_secret_access_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_s3_bucket_name
AWS_URL=https://your_s3_bucket_name.s3.amazonaws.com
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_CDN_ENABLED=false
AWS_CDN_URL=

# Set default filesystem to S3
FILESYSTEM_DISK=s3
```

### 2. Filesystem Configuration

The S3 disk is already configured in `config/filesystems.php`:

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => false,
    'report' => false,
],
```

### 3. S3 Configuration

Additional S3 settings are available in `config/s3.php`:

- File upload folders
- Allowed file types
- File size limits
- CDN configuration

## Usage

### 1. Using S3Service

The `S3Service` class provides methods for common S3 operations:

```php
use App\Services\S3Service;

class YourController extends Controller
{
    protected $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    public function uploadFile(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $this->s3Service->uploadFile($file, 'uploads');
            
            return response()->json([
                'path' => $path,
                'url' => $this->s3Service->getFileUrl($path)
            ]);
        }
    }

    public function deleteFile($path)
    {
        $deleted = $this->s3Service->deleteFile($path);
        
        return response()->json([
            'deleted' => $deleted
        ]);
    }
}
```

### 2. Using HasS3Uploads Trait

For controllers that need S3 functionality, use the `HasS3Uploads` trait:

```php
use App\Traits\HasS3Uploads;

class YourController extends Controller
{
    use HasS3Uploads;

    public function upload(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // Validate file
            if (!$this->validateFile($file, 'image')) {
                return response()->json([
                    'error' => $this->getFileValidationMessage('image')
                ], 400);
            }
            
            // Upload to S3
            $path = $this->uploadToS3($file, 'images');
            
            return response()->json([
                'path' => $path,
                'url' => $this->getS3Url($path)
            ]);
        }
    }
}
```

### 3. Direct Storage Usage

You can also use Laravel's Storage facade directly:

```php
use Illuminate\Support\Facades\Storage;

// Upload file
$path = Storage::disk('s3')->put('folder/file.jpg', $fileContents);

// Get file URL
$url = Storage::disk('s3')->url($path);

// Delete file
Storage::disk('s3')->delete($path);

// Check if file exists
$exists = Storage::disk('s3')->exists($path);
```

## Testing

### 1. Test S3 Connection

Run the provided Artisan command to test your S3 configuration:

```bash
php artisan s3:test
```

For a more comprehensive test including file upload:

```bash
php artisan s3:test --upload-test-file
```

### 2. Manual Testing

You can test the S3 integration manually:

1. Set up your environment variables
2. Run the test command
3. Check the console output for any errors
4. Verify files are uploaded to your S3 bucket

## File Structure

The S3 integration includes the following files:

```
app/
├── Services/
│   └── S3Service.php          # Main S3 service class
├── Traits/
│   └── HasS3Uploads.php       # Reusable S3 upload trait
└── Console/Commands/
    └── TestS3Connection.php   # S3 connection test command

config/
├── filesystems.php            # Laravel filesystem configuration
└── s3.php                     # S3-specific configuration

env.example                    # Environment variables template
S3_INTEGRATION.md             # This documentation file
```

## Migration from Local Storage

If you're migrating from local storage to S3:

1. **Update Controllers**: Replace local storage calls with S3 calls
2. **Migrate Existing Files**: Upload existing files to S3
3. **Update URLs**: Update any hardcoded file URLs
4. **Test Thoroughly**: Ensure all file operations work correctly

### Example Migration

Before (Local Storage):
```php
$path = $image->store('projects', 'public');
```

After (S3):
```php
$path = $this->s3Service->uploadFile($image, 'projects');
```

## Troubleshooting

### Common Issues

1. **Access Denied Error**
   - Check your AWS credentials
   - Verify IAM permissions
   - Ensure bucket name is correct

2. **Region Mismatch**
   - Make sure the region in your `.env` matches your S3 bucket region

3. **File Upload Failures**
   - Check file size limits
   - Verify file types are allowed
   - Ensure bucket has proper permissions

4. **URL Generation Issues**
   - Verify `AWS_URL` is set correctly
   - Check if bucket is public or has proper CORS settings

### Debug Commands

```bash
# Test S3 connection
php artisan s3:test

# Check configuration
php artisan config:show filesystems.disks.s3

# Clear configuration cache
php artisan config:clear
```

### Logs

Check Laravel logs for detailed error messages:

```bash
tail -f storage/logs/laravel.log
```

## Security Best Practices

1. **Never commit AWS credentials** to version control
2. **Use IAM roles** instead of access keys when possible
3. **Limit bucket permissions** to only what's necessary
4. **Enable bucket versioning** for important files
5. **Set up proper CORS** if serving files to web browsers
6. **Use presigned URLs** for temporary file access

## Performance Optimization

1. **Use CDN**: Configure CloudFront for better performance
2. **Optimize file sizes**: Compress images before upload
3. **Batch operations**: Use batch uploads for multiple files
4. **Caching**: Cache frequently accessed file URLs

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Verify your AWS configuration
3. Review Laravel and AWS SDK documentation
4. Check the project's issue tracker

## Additional Resources

- [Laravel File Storage Documentation](https://laravel.com/docs/storage)
- [AWS S3 PHP SDK Documentation](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/s3.html)
- [Flysystem AWS S3 Adapter](https://flysystem.thephpleague.com/docs/adapter/aws-s3-v3/)
