<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\Project;
use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Use caching for better performance (cache for 10 minutes)
            $cacheKey = 'categories_page_' . request()->get('page', 1);

            $categories = Cache::remember($cacheKey, 600, function () {
                return Category::select('id', 'name', 'description', 'cover', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->paginate(15);
            });

            // Transform data for frontend
            $categories->getCollection()->transform(function ($category) {
                $coverData = null;
                if ($category->cover) {
                    // Get base64 image data from ImageStorage
                    $imageStorage = \App\Models\ImageStorage::where('path', $category->cover)->first();
                    $coverData = $imageStorage ? 'data:' . $imageStorage->mime_type . ';base64,' . $imageStorage->image_data : null;
                }

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'cover' => $coverData, // Return base64 data
                    'created_at' => $category->created_at
                ];
            });

            return response()->json([
                'status' => 'success',
                'categories' => $categories->items(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch categories'
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        try {
            Log::info('Category store request data:', [
                'all_data' => $request->all(),
                'has_files' => $request->hasFile('cover'),
                'files' => $request->file('cover'),
            ]);

            $createDataCategory = $request->validated();
            Log::info('Validated category data:', $createDataCategory);

            $categories = [];
            $names = $createDataCategory['name'];
            $descriptions = $createDataCategory['description'] ?? [];
            $covers = $createDataCategory['cover'] ?? [];

            for ($i = 0; $i < count($names); $i++) {
                $categoryData = [
                    'name' => $names[$i],
                    'description' => $descriptions[$i] ?? null,
                ];

                // Handle cover image if provided
                if (isset($covers[$i]) && $covers[$i]) {
                    $file = $covers[$i];
                    $imageStorageService = app(ImageStorageService::class);
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = 'category_covers/' . $filename;
                    $imageStorage = $imageStorageService->storeImage($file, $path);
                    $categoryData['cover'] = $path;
                }

                $category = Category::create($categoryData);
                $categories[] = $category;
            }

            // Clear categories cache after create
            for ($page = 1; $page <= 10; $page++) {
                Cache::forget('categories_page_' . $page);
            }

            return response()->json([
                'message' => 'Catégories ajoutées avec succès',
                'categories' => $categories,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating categories: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la création des catégories'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // find category by id
        $category = Category::find($id);

        // check if category exists
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // response Api
        return response()->json([
            'message' => 'Catégorie récupérée avec succès',
            'category' => $category,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $updateDataCategory = $request->validated();

        if ($request->hasFile('cover')) {
            $file = $request->file('cover');

            // Delete old image from database if exists
            if ($category->cover) {
                $imageStorageService = app(ImageStorageService::class);
                $imageStorageService->deleteImage($category->cover);
            }

            // Store new image in database using ImageStorageService
            $imageStorageService = app(ImageStorageService::class);
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = 'category_covers/' . $filename;

            $imageStorage = $imageStorageService->storeImage($file, $path);
            $updateDataCategory['cover'] = $path;
        }

        $category->update($updateDataCategory);

        // Clear categories cache after update
        Cache::forget('categories_page_1'); // Clear first page cache
        // Clear all possible category cache pages (up to 10 pages)
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget('categories_page_' . $page);
        }

        return response()->json([
            'message' => 'Catégorie mise à jour avec succès',
            'category' => $category,
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            Log::info('Category delete request for ID: ' . $id);

            // find by id
            $category = Category::find($id);

            // check if category exists
            if (!$category) {
                Log::warning('Category not found with ID: ' . $id);
                return response()->json(['message' => 'Category not found'], 404);
            }

            // Check if category has associated projects with retry logic
            $maxRetries = 3;
            $projectCount = 0;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $projectCount = $category->projects()->count();
                    break; // Success, exit retry loop
                } catch (\Exception $dbError) {
                    Log::warning("Database query attempt $attempt failed for category $id: " . $dbError->getMessage());
                    if ($attempt === $maxRetries) {
                        throw $dbError; // Re-throw on final attempt
                    }
                    usleep(100000); // Wait 100ms before retry
                }
            }

            if ($projectCount > 0) {
                // Get the actual project names for better user feedback
                $projectNames = $category->projects()->select('id', 'name')->get()->toArray();

                Log::warning('Cannot delete category with ID: ' . $id . ' - has ' . $projectCount . ' associated projects', [
                    'projects' => $projectNames
                ]);

                return response()->json([
                    'message' => 'Cannot delete category that has associated projects. Please remove projects first.',
                    'project_count' => $projectCount,
                    'projects' => $projectNames
                ], 422);
            }

            Log::info('Category ' . $id . ' has no associated projects, proceeding with deletion');

            // Delete category cover image if exists
            if ($category->cover) {
                try {
                    $imageStorageService = app(ImageStorageService::class);
                    $imageStorageService->deleteImage($category->cover);
                    Log::info('Category cover image deleted successfully: ' . $category->cover);
                } catch (\Exception $imageError) {
                    Log::warning('Failed to delete category cover image: ' . $category->cover . ' - ' . $imageError->getMessage());
                    // Continue with category deletion even if image deletion fails
                }
            }

            // delete category with additional error handling
            try {
                $category->delete();
                Log::info('Category record deleted successfully with ID: ' . $id);
            } catch (\Exception $deleteError) {
                Log::error('Failed to delete category record with ID: ' . $id . ' - ' . $deleteError->getMessage());
                throw $deleteError; // Re-throw to be caught by outer try-catch
            }

            // Clear categories cache after delete
            try {
                for ($page = 1; $page <= 10; $page++) {
                    Cache::forget('categories_page_' . $page);
                }
                Log::info('Category cache cleared successfully');
            } catch (\Exception $cacheError) {
                Log::warning('Failed to clear category cache: ' . $cacheError->getMessage());
                // Don't fail deletion if cache clearing fails
            }

            Log::info('Category deleted successfully with ID: ' . $id);

            // response Api
            return response()->json([
                'message' => 'Catégorie supprimée avec succès',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting category with ID: ' . $id . ' - ' . $e->getMessage(), [
                'exception_type' => get_class($e),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la suppression de la catégorie',
                'error_details' => $e->getMessage(),
                'error_type' => get_class($e)
            ], 500);
        }
    }

    /**
     * Get projects by category ID
     */
    public function getProjectsByCategory($id)
    {
        Log::info('getProjectsByCategory called with ID: ' . $id);

        // Optional: validate the category exists
        $category = Category::find($id);
        if (!$category) {
            Log::error('Category not found with ID: ' . $id);
            return response()->json(['message' => 'Category not found'], 404);
        }

        Log::info('Category found: ' . $category->name);

        // Fetch projects with their images
        $projects = Project::with('images')->where('category_id', $id)->get();

        Log::info('Projects found: ' . $projects->count());

        if ($projects->isEmpty()) {
            Log::warning('No projects found for category ID: ' . $id);
            return response()->json(['message' => 'No projects found for this category'], 404);
        }

        // Transform projects to include base64 image data
        $transformedProjects = $projects->map(function ($project) {
            $coverImage = null;
            $transformedImages = $project->images->map(function ($image) use (&$coverImage) {
                $imageStorage = \App\Models\ImageStorage::where('path', $image->image_url)->first();
                $base64Data = $imageStorage ? 'data:' . $imageStorage->mime_type . ';base64,' . $imageStorage->image_data : null;

                $transformedImage = [
                    'id' => $image->id,
                    'image_url' => $base64Data,
                    'is_cover' => $image->is_cover
                ];

                // Set cover image if this is marked as cover
                if ($image->is_cover && $base64Data) {
                    $coverImage = $base64Data;
                }

                return $transformedImage;
            });

            // If no cover image is set, use the first image as cover
            if (!$coverImage && $transformedImages->isNotEmpty()) {
                $coverImage = $transformedImages->first()['image_url'];
            }

            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'images' => $transformedImages,
                'cover_image' => $coverImage,
                'category_id' => $project->category_id,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at
            ];
        });

        return response()->json([
            'message' => 'Projects retrieved successfully',
            'projects' => $transformedProjects
        ], 200);
    }
}
