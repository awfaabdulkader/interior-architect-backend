<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
    protected $fillable =
    [
        'user_id',
        'cv_fr_data',
        'cv_fr_filename',
        'cv_fr_mime_type',
        'cv_fr_size',
        'cv_fr_uploaded_at',
        'cv_en_data',
        'cv_en_filename',
        'cv_en_mime_type',
        'cv_en_size',
        'cv_en_uploaded_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
