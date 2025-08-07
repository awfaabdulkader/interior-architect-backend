<?php

namespace App\Http\Controllers;

use App\Models\Project_image;
use Illuminate\Http\Request;
use App\Services\S3Service;

class ProjectImageController extends Controller
{
    protected $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Project_image $project_image)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project_image $project_image)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project_image $project_image)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project_image $project_image)
    {
        //
    }
}
