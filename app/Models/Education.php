<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $fillable = [
        'user_id',
        'year_start',
        'year_end',
        'diploma',
        'school',
    ];

    protected $casts = [
        'year_start' => 'datetime:Y-m-d',
        'year_end' => 'datetime:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
