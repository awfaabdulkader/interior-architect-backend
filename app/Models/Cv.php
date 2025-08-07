<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
    protected $fillable = 
    [
        'user_id',
        'cv_fr',
        'cv_en',
    ];


       public function user()
    {
        return $this->belongsTo(User::class);
    }
}
