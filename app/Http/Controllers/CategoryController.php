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
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'cover' => $category->cover,
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

            // Clear cache after creating new categories
            Cache::forget('categories_page_1');
            Cache::forget('categories_page_2');

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
        // find by id
        $category = Category::find($id);

        // check if category exists
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // delete category
        $category->delete();

        // response Api
        return response()->json([
            'message' => 'Catégorie supprimée avec succès',
        ], 200);
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

        return response()->json([
            'message' => 'Projects retrieved successfully',
            'projects' => $projects
        ], 200);
    }
}
