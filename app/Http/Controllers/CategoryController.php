<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        // check if categories exist
        if ($categories->isEmpty()) {
            return response()->json(['message' => 'No categories found'], 404);
        }

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'categories' => $categories,
        ], 200);
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
        // validate data
        $createDataCategory = $request->validated();

        $created = [];

        // create category 
        foreach ($createDataCategory['name'] as $index => $name) {
            $description = $createDataCategory['description'][$index] ?? null;

            $cover = null;
            if ($request->hasFile("cover.$index")) {
                $file = $request->file("cover.$index");
                $filename = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('category_covers', $filename, 'public');
                $cover = $filePath;
            }
            $created[] = Category::create([
                'name' => $name,
                'description' => $description,
                'cover' => $cover,
            ]);
        }

        // response Api
        return response()->json([
            'message' => 'Category created successfully',
            'category' => $created,
        ], 201);
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
            'message' => 'Category retrieved successfully',
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
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('category_covers', $filename, 'public');
            $updateDataCategory['cover'] = $filePath;
        }

        $category->update($updateDataCategory);

        return response()->json([
            'message' => 'Category updated successfully',
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
            'message' => 'Category deleted successfully',
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
