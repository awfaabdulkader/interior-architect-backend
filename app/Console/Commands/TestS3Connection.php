<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\S3Service;

class TestS3Connection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:test {--upload-test-file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test S3 connection and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing S3 Connection...');

        try {
            // Test basic connectivity
            $this->info('1. Testing basic connectivity...');
            $disk = Storage::disk('s3');

            if ($disk) {
                $this->info('✓ S3 disk configured successfully');
            } else {
                $this->error('✗ Failed to configure S3 disk');
                return 1;
            }

            // Test bucket access
            $this->info('2. Testing bucket access...');
            $bucket = config('filesystems.disks.s3.bucket');
            $this->info("Bucket: {$bucket}");

            try {
                $disk->put('test-connection.txt', 'S3 connection test - ' . now());
                $this->info('✓ Successfully wrote to S3 bucket');

                // Clean up test file
                $disk->delete('test-connection.txt');
                $this->info('✓ Successfully deleted test file');
            } catch (\Exception $e) {
                $this->error('✗ Failed to write to S3 bucket: ' . $e->getMessage());
                return 1;
            }

            // Test S3Service
            $this->info('3. Testing S3Service...');
            $s3Service = app(S3Service::class);

            if ($s3Service) {
                $this->info('✓ S3Service instantiated successfully');
            } else {
                $this->error('✗ Failed to instantiate S3Service');
                return 1;
            }

            // Test URL generation
            $this->info('4. Testing URL generation...');
            $testPath = 'test/example.jpg';
            $url = $s3Service->getFileUrl($testPath);
            $this->info("Generated URL: {$url}");

            // Test file existence check
            $this->info('5. Testing file existence check...');
            $exists = $s3Service->fileExists($testPath);
            $this->info("File exists check: " . ($exists ? 'true' : 'false'));

            // Optional: Upload a test file
            if ($this->option('upload-test-file')) {
                $this->info('6. Uploading test file...');

                // Create a temporary test file
                $testContent = 'This is a test file uploaded at ' . now();
                $tempFile = tempnam(sys_get_temp_dir(), 's3_test_');
                file_put_contents($tempFile, $testContent);

                // Create UploadedFile instance
                $uploadedFile = new \Illuminate\Http\UploadedFile(
                    $tempFile,
                    'test-file.txt',
                    'text/plain',
                    null,
                    true
                );

                $path = $s3Service->uploadFile($uploadedFile, 'test');
                $this->info("✓ Test file uploaded to: {$path}");

                // Get the URL
                $fileUrl = $s3Service->getFileUrl($path);
                $this->info("File URL: {$fileUrl}");

                // Clean up
                unlink($tempFile);
            }

            $this->info('');
            $this->info('🎉 S3 connection test completed successfully!');
            $this->info('');
            $this->info('Configuration Summary:');
            $this->info('- Region: ' . config('filesystems.disks.s3.region'));
            $this->info('- Bucket: ' . config('filesystems.disks.s3.bucket'));
            $this->info('- URL: ' . config('filesystems.disks.s3.url'));

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ S3 connection test failed: ' . $e->getMessage());
            $this->error('');
            $this->error('Please check your AWS credentials and configuration:');
            $this->error('- AWS_ACCESS_KEY_ID');
            $this->error('- AWS_SECRET_ACCESS_KEY');
            $this->error('- AWS_DEFAULT_REGION');
            $this->error('- AWS_BUCKET');

            return 1;
        }
    }
}
