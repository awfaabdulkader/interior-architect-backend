<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\Project_image;
use App\Models\ImageStorage;

echo "=== PROJECT IMAGES ANALYSIS ===\n\n";

$projects = Project::with('images')->get();

foreach ($projects as $project) {
    echo "Project: {$project->name} (ID: {$project->id})\n";
    echo "Images count: " . $project->images->count() . "\n";

    foreach ($project->images as $image) {
        $isCover = $image->is_cover ? 'YES' : 'NO';
        echo "  - Image ID: {$image->id}, Path: {$image->image_url}, Cover: {$isCover}\n";

        // Check if this path exists in ImageStorage
        $storage = ImageStorage::where('path', $image->image_url)->first();
        if ($storage) {
            echo "    Storage: Found, MIME: {$storage->mime_type}, Size: " . strlen($storage->image_data) . " bytes\n";
        } else {
            echo "    Storage: NOT FOUND!\n";
        }
    }
    echo "\n";
}

echo "=== SUMMARY ===\n";
$totalImages = Project_image::count();
$coversSet = Project_image::where('is_cover', true)->count();
$uniquePaths = Project_image::distinct('image_url')->count();

echo "Total images: {$totalImages}\n";
echo "Covers set: {$coversSet}\n";
echo "Unique image paths: {$uniquePaths}\n";

if ($uniquePaths < $totalImages) {
    echo "⚠️  WARNING: Some projects share the same image files!\n";
}

echo "\nDone.\n";
