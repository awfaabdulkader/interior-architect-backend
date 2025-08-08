<?php

namespace App\Console\Commands;

use App\Models\ImageStorage;
use App\Models\Project;
use App\Models\Category;
use App\Models\Skill;
use App\Models\Cv;
use App\Services\ImageStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateImagesToDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:images-to-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing images from storage to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting image migration to database...');

        $migratedCount = 0;
        $errorCount = 0;

        // Migrate project images
        $this->info('📁 Migrating project images...');
        $projects = Project::with('images')->get();

        foreach ($projects as $project) {
            foreach ($project->images as $image) {
                if ($this->migrateImage($image->image_url, 'projects')) {
                    $migratedCount++;
                } else {
                    $errorCount++;
                }
            }
        }

        // Migrate category covers
        $this->info('📁 Migrating category covers...');
        $categories = Category::whereNotNull('cover')->get();

        foreach ($categories as $category) {
            if ($this->migrateImage($category->cover, 'category_covers')) {
                $migratedCount++;
            } else {
                $errorCount++;
            }
        }

        // Migrate skill logos
        $this->info('📁 Migrating skill logos...');
        $skills = Skill::whereNotNull('logo')->get();

        foreach ($skills as $skill) {
            if ($this->migrateImage($skill->logo, 'logos')) {
                $migratedCount++;
            } else {
                $errorCount++;
            }
        }

        // Migrate CV files
        $this->info('📁 Migrating CV files...');
        $cvs = Cv::whereNotNull('cv_fr')->orWhereNotNull('cv_en')->get();

        foreach ($cvs as $cv) {
            if ($cv->cv_fr && $this->migrateImage($cv->cv_fr, 'cvs')) {
                $migratedCount++;
            }
            if ($cv->cv_en && $this->migrateImage($cv->cv_en, 'cvs')) {
                $migratedCount++;
            }
        }

        $this->info("✅ Migration completed!");
        $this->info("📊 Migrated: {$migratedCount} images");
        $this->info("❌ Errors: {$errorCount} images");
    }

    private function migrateImage($path, $folder)
    {
        try {
            // Check if image already exists in database
            if (ImageStorage::where('path', $path)->exists()) {
                $this->line("⏭️  Skipping {$path} - already in database");
                return true;
            }

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($path)) {
                $this->error("❌ File not found: {$path}");
                return false;
            }

            // Get file content
            $fileContent = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            $filename = basename($path);

            // Create image storage record
            ImageStorage::create([
                'filename' => $filename,
                'image_data' => base64_encode($fileContent),
                'mime_type' => $mimeType,
                'path' => $path,
                'size' => strlen($fileContent)
            ]);

            $this->line("✅ Migrated: {$path}");
            return true;
        } catch (\Exception $e) {
            $this->error("❌ Error migrating {$path}: " . $e->getMessage());
            return false;
        }
    }
}
