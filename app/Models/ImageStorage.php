<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageStorage extends Model
{
    protected $table = 'image_storage';

    protected $fillable = [
        'filename',
        'image_data',
        'mime_type',
        'path',
        'size'
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the image URL for display
     */
    public function getImageUrlAttribute()
    {
        return route('api.images.show', ['path' => $this->path]);
    }

    /**
     * Get the image data as binary
     */
    public function getImageBinaryAttribute()
    {
        return base64_decode($this->image_data);
    }
}
