<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Project_image;
use App\Models\ImageStorage;
use App\Models\Category;
use App\Models\User;

class ProjectTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (or create one if none exists)
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now()
            ]);
        }

        // Get the first category (or create one if none exists)
        $category = Category::first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Test Category',
                'description' => 'Category for testing pagination'
            ]);
        }

        // Generate a simple base64 image (1x1 pixel PNG)
        $sampleImageBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        echo "Creating 30 test projects with images...\n";

        for ($i = 1; $i <= 30; $i++) {
            echo "Creating project $i...\n";

            // Create project
            $project = Project::create([
                'user_id' => $user->id,
                'name' => "Test Project $i",
                'description' => "This is test project number $i created for pagination testing. It contains sample data to verify the lazy loading functionality works correctly with multiple projects.",
                'category_id' => $category->id,
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()
            ]);

            // Create 2-4 images per project
            $imageCount = rand(2, 4);

            for ($j = 1; $j <= $imageCount; $j++) {
                // Create ImageStorage record
                $imagePath = "test_projects/project_{$i}_image_{$j}.png";

                $imageStorage = ImageStorage::create([
                    'filename' => "project_{$i}_image_{$j}.png",
                    'image_data' => $sampleImageBase64,
                    'mime_type' => 'image/png',
                    'path' => $imagePath,
                    'size' => strlen(base64_decode($sampleImageBase64))
                ]);

                // Create Project_image record
                Project_image::create([
                    'project_id' => $project->id,
                    'image_url' => $imagePath,
                    'is_cover' => $j === 1 // First image is the cover
                ]);
            }
        }

        echo "✅ Successfully created 30 test projects with images!\n";
        echo "Category: {$category->name} (ID: {$category->id})\n";
        echo "🔗 Test URL: /categories/{$category->id}\n";
    }
}
