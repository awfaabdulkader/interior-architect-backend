<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ProjectRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Optimize query with pagination and eager loading
            $projects = Project::with(['category:id,name,cover', 'images:id,project_id,image_url,is_cover'])
                ->select('id', 'name', 'description', 'category_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(12); // Load 12 projects per page

            // Transform data for frontend
            $projects->getCollection()->transform(function ($project) {
                // Get cover image (first check for is_cover=true, otherwise use first image)
                $coverImage = $project->images->where('is_cover', true)->first() ?? $project->images->first();
                $coverImageData = null;

                if ($coverImage) {
                    $imageStorage = \App\Models\ImageStorage::where('path', $coverImage->image_url)->first();
                    $coverImageData = $imageStorage ? 'data:' . $imageStorage->mime_type . ';base64,' . $imageStorage->image_data : null;
                }

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'cover_image' => $coverImageData, // Single cover image for thumbnails
                    'images' => $project->images->map(function ($image) {
                        // Get base64 image data from ImageStorage
                        $imageStorage = \App\Models\ImageStorage::where('path', $image->image_url)->first();
                        $base64Data = $imageStorage ? 'data:' . $imageStorage->mime_type . ';base64,' . $imageStorage->image_data : null;

                        return [
                            'id' => $image->id,
                            'image_url' => $base64Data,
                            'is_cover' => $image->is_cover
                        ];
                    }),
                    'category' => $project->category ? [
                        'id' => $project->category->id,
                        'name' => $project->category->name,
                        'cover' => $project->category->cover
                    ] : null,
                    'created_at' => $project->created_at
                ];
            });

            return response()->json([
                'status' => 'success',
                'projects' => $projects->items(),
                'pagination' => [
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching projects: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch projects'
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
    public function store(ProjectRequest $request)
    {
        // validate data
        $validatedProjectData = $request->validated();
        $validatedProjectData['user_id'] = Auth::id();
        // check if project already exists
        $esistingProjects = Project::where('name', $validatedProjectData['name'])
            ->where('description', $validatedProjectData['description'])
            ->where('category_id', $validatedProjectData['category_id'])
            ->first();
        if ($esistingProjects) {
            return response()->json([
                'message' => 'Le projet existe déjà',
                'project' => $esistingProjects,
            ], 409); // Conflict status code
        }

        // create new project
        $project = Project::create($validatedProjectData);

        // handle file upload if images are provided
        if ($request->hasFile('images')) {
            $images = $request->file('images');

            // Wrap single file in array for consistency
            $images = is_array($images) ? $images : [$images];

            foreach ($images as $image) {
                // Store image in database using ImageStorageService
                $imageStorageService = app(ImageStorageService::class);
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = 'projects/' . $filename;

                $imageStorage = $imageStorageService->storeImage($image, $path);

                $project->images()->create([
                    'image_url' => $path,
                ]);
            }
        }


        // response Api
        return response()->json([
            'message' => 'Projet créé avec succès',
            'project' => $project,
        ], 201); // Created status code



    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $project = Project::with(['images', 'category'])->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Projet non trouvé'
            ], 404);
        }

        // Transform project data to include base64 images
        $transformedProject = [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'images' => $project->images->map(function ($image) {
                // Get base64 image data from ImageStorage
                $imageStorage = \App\Models\ImageStorage::where('path', $image->image_url)->first();
                $base64Data = $imageStorage ? 'data:' . $imageStorage->mime_type . ';base64,' . $imageStorage->image_data : null;

                return [
                    'id' => $image->id,
                    'image_url' => $base64Data,
                    'is_cover' => $image->is_cover
                ];
            }),
            'category' => $project->category ? [
                'id' => $project->category->id,
                'name' => $project->category->name,
                'cover' => $project->category->cover
            ] : null,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at
        ];

        return response()->json([
            'success' => true,
            'project' => $transformedProject
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProjectRequest $request, $id)
    {
        // Debug: Log what's being received
        Log::info('Update project request data:', [
            'id' => $id,
            'has_files' => $request->hasFile('images'),
            'files_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
            'keep_image_ids' => $request->input('keep_image_ids'),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'category_id' => $request->input('category_id'),
        ]);

        //find by id
        $project = Project::find($id);

        //check if exists
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        //validate data
        $validatedProjectData = $request->validated();

        Log::info('Validated data:', $validatedProjectData);

        // Handle explicit image deletions FIRST (before adding new ones)
        if ($request->has('keep_image_ids')) {
            $keepImageIds = json_decode($request->input('keep_image_ids'), true);
            Log::info('Keep image IDs:', $keepImageIds);

            // Get current project images (before adding new ones)
            $currentImages = $project->images;

            // Delete images that are NOT in the keep list
            foreach ($currentImages as $image) {
                if (!in_array($image->id, $keepImageIds)) {
                    Log::info('Deleting image with ID: ' . $image->id);
                    $imageStorageService = app(ImageStorageService::class);
                    $imageStorageService->deleteImage($image->image_url);
                    $image->delete();
                }
            }
        }

        // THEN add new images (after cleaning up unwanted existing ones)
        Log::info('Checking for new images...', [
            'hasFile_images' => $request->hasFile('images'),
            'file_images_exists' => !is_null($request->file('images')),
            'all_files' => $request->allFiles()
        ]);

        if ($request->hasFile('images') && $request->file('images')) {
            Log::info('Adding new images to project');

            // save new images to database
            $images = $request->file('images');
            $images = is_array($images) ? $images : [$images];

            foreach ($images as $image) {
                // Store image in database using ImageStorageService
                $imageStorageService = app(ImageStorageService::class);
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = 'projects/' . $filename;

                $imageStorage = $imageStorageService->storeImage($image, $path);

                $project->images()->create([
                    'image_url' => $path,
                ]);

                Log::info('New image added with path: ' . $path);
            }
        }

        // update project
        $project->update($validatedProjectData);

        // response Api
        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project,
        ], 200); // OK status code
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        // Delete images from database
        foreach ($project->images as $image) {
            $imageStorageService = app(ImageStorageService::class);
            $imageStorageService->deleteImage($image->image_url);
            $image->delete(); // delete from DB
        }

        // Delete project
        $project->delete();

        return response()->json(['message' => 'Project and its images deleted successfully'], 200);
    }

    public function getProjectsByCategory($id)
    {
        // Optional: validate the category exists
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Fetch projects with their images including cover info
        $projects = Project::with(['images:id,project_id,image_url,is_cover'])
            ->select('id', 'name', 'description', 'category_id', 'created_at')
            ->where('category_id', $id)
            ->get();

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'No projects found for this category'], 404);
        }

        // Transform projects to include cover_image
        $transformedProjects = $projects->map(function ($project) {
            // Get cover image (first check for is_cover=true, otherwise use first image)
            $coverImage = $project->images->where('is_cover', true)->first() ?? $project->images->first();
            $coverImageData = null;

            if ($coverImage) {
                $imageStorage = \App\Models\ImageStorage::where('path', $coverImage->image_url)->first();
                $coverImageData = $imageStorage ? 'data:' . $imageStorage->mime_type . ';base64,' . $imageStorage->image_data : null;
            }

            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'category_id' => $project->category_id,
                'cover_image' => $coverImageData, // Single cover image for thumbnails
                'images' => $project->images->map(function ($image) {
                    // Get base64 image data from ImageStorage
                    $imageStorage = \App\Models\ImageStorage::where('path', $image->image_url)->first();
                    $base64Data = $imageStorage ? 'data:' . $imageStorage->mime_type . ';base64,' . $imageStorage->image_data : null;

                    return [
                        'id' => $image->id,
                        'image_url' => $base64Data,
                        'is_cover' => $image->is_cover
                    ];
                }),
                'created_at' => $project->created_at
            ];
        });

        return response()->json([
            'message' => 'Projects retrieved successfully',
            'projects' => $transformedProjects
        ], 200);
    }

    /**
     * Set cover image for a project
     */
    public function setCoverImage(Request $request, $projectId)
    {
        try {
            $request->validate([
                'image_id' => 'required|integer|exists:project_images,id'
            ]);

            $project = Project::findOrFail($projectId);
            $imageId = $request->image_id;

            // Verify the image belongs to this project
            $image = $project->images()->where('id', $imageId)->first();
            if (!$image) {
                return response()->json([
                    'message' => 'Image not found for this project'
                ], 404);
            }

            // Remove is_cover from all images of this project
            $project->images()->update(['is_cover' => false]);

            // Set the selected image as cover
            $image->update(['is_cover' => true]);

            return response()->json([
                'message' => 'Cover image updated successfully',
                'cover_image_id' => $imageId
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error setting cover image: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error setting cover image',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
