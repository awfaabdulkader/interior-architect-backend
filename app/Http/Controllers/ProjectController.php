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
            $projects = Project::with(['category:id,name,cover', 'images:id,project_id,image_url'])
                ->select('id', 'name', 'description', 'category_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate(12); // Load 12 projects per page

            // Transform data for frontend
            $projects->getCollection()->transform(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'images' => $project->images->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_url' => $image->image_url
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

        return response()->json([
            'success' => true,
            'project' => $project
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
            'all_data' => $request->all(),
            'has_files' => $request->hasFile('images'),
            'files' => $request->file('images'),
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

        // upload new images if provided and delete old images
        if ($request->hasFile('images') && $request->file('images')) {
            // delete old images from database
            foreach ($project->images as $image) {
                $imageStorageService = app(ImageStorageService::class);
                $imageStorageService->deleteImage($image->image_url);
                $image->delete();
            }

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

        // Fetch projects with their images
        $projects = Project::with('images')->where('category_id', $id)->get();

        if ($projects->isEmpty()) {
            return response()->json(['message' => 'No projects found for this category'], 404);
        }

        return response()->json([
            'message' => 'Projects retrieved successfully',
            'projects' => $projects
        ], 200);
    }
}
