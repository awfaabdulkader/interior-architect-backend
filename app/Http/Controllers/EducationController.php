<?php

namespace App\Http\Controllers;

use App\Http\Requests\EducationRequest;
use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EducationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Optimize query with proper ordering and selective fields
            $allEducation = Education::select(['id', 'year_start', 'year_end', 'diploma', 'school'])
                ->orderBy('year_end', 'desc')
                ->orderBy('year_start', 'desc')
                ->get();

            // Always return fresh data with no caching
            return response()->json([
                'message' => 'Education retrieved successfully',
                'education' => $allEducation,
            ], 200)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Error fetching education: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error retrieving education data',
                'error' => $e->getMessage()
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
    public function store(EducationRequest $request)
    {
        // validate data
        $createDataEducation = $request->validated();
        $createDataEducation['user_id'] = auth()->id(); // get the current user ID

        // create education
        $storeEducation = Education::create($createDataEducation);

        // response Api
        return response()->json([
            'message' => 'Education created successfully',
            'education' => $storeEducation,
        ], 201)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $education = Education::find($id);
        // check if exists
        if (!$education) {
            return response()->json(['message' => 'Education not found'], 404);
        }
        // response Api
        return response()->json([
            'message' => 'Education retrieved successfully',
            'education' => $education,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Education $education)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EducationRequest $request, $id)
    {
        // find by id
        $education = Education::find($id);
        // check if exists
        if (!$education) {
            return response()->json(['message' => 'Education not found'], 404);
        }
        // validate data
        $educationDatata = $request->validated();

        //update data
        $education->update($educationDatata);
        // response Api
        return response()->json([
            'message' => 'Education updated successfully',
            'education' => $education,
        ], 200)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //find by id
        $education = Education::find($id);
        //check if exists
        if (!$education) {
            return response()->json(['message' => 'Education not found'], 404);
        }
        //delete
        $education->delete();
        //respone api
        return response()->json([
            'message' => 'Education deleted successfully',
        ], 200)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
