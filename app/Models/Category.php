<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable=
    [
        'name',
        'description',
        'cover', // path to image
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
