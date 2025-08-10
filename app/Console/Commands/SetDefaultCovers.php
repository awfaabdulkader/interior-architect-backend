<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Project_image;

class SetDefaultCovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:set-default-covers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the first image of each project as cover if no cover is set';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projects = Project::with('images')->get();
        $updated = 0;

        foreach ($projects as $project) {
            $hasCover = $project->images()->where('is_cover', true)->exists();

            if (!$hasCover && $project->images->count() > 0) {
                $firstImage = $project->images->first();
                $firstImage->update(['is_cover' => true]);
                $this->info("Set cover for project '{$project->name}' (ID: {$project->id}) to image ID: {$firstImage->id}");
                $updated++;
            }
        }

        $this->info("Updated {$updated} projects with default cover images.");
        return Command::SUCCESS;
    }
}
