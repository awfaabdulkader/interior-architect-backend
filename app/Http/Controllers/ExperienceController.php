<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ExperienceRequest;

class ExperienceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // display all experiences
        $experience= Experience::all();

        // check if experiences exist
        if($experience->isEmpty()) {
            return response()->json(['message' => 'No experiences found'], 404);
        }

        //response Api
        return response()->json([
            'message' => 'Experiences retrieved successfully',
            'experiences' => $experience,
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
    public function store(ExperienceRequest $request)
{
    $experienceData = $request->validated();
    $experienceData['user_id'] = auth()->id();

     // If currently working, make sure year_end is null
    if (!empty($experienceData['currently_working'])) {
        $experienceData['year_end'] = null;
    }


    $experience = Experience::create($experienceData);

    // 🟢 Debug here:
   return response()->json([
       'message' => 'Experience created successfully',
       'experience' => $experience,
   ], 201);
}


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // find experience by id
        $experience = Experience::find($id);
        // check if exists
        if (!$experience) {
            return response()->json(['message' => 'Experience not found'], 404);
        }

        // response Api
        return response()->json([
            'message' => 'Experience retrieved successfully',
            'experience' => $experience,
        ], 200); // OK status code
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Experience $experience)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExperienceRequest $request, $id)
    {
        //find by id
        $experience= Experience::find($id);
        //check if exists
        if(!$experience)
        {
            return response()->json(['message' => 'Experience not found'], 404);
        }
        //validate data
        $experienceValidated = $request->validated();

        //update experience
        $experience->update($experienceValidated);

        // response Api
        return response()->json([
            'message' => 'Experience updated successfully',
            'experience' => $experience,
        ], 200); // OK status code
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
         //find by id
        $experience= Experience::find($id);
        //check if exists
        if(!$experience)
        {
            return response()->json(['message' => 'Experience not found'], 404);
        }

        //delete experience
        $experience->delete();
        
        // response Api
        return response()->json([
            'message' => 'Experience deleted successfully',
        ], 200); // OK status code
    }
}
