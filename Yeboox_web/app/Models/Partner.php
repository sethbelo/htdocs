<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'tagline', 'description', 'website_url',
        'logo_path', 'gallery_images', 'initials', 'brand_color', 'is_verified', 'is_featured',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];
}
