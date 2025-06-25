<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'business_id',
        'template_id',
        'name',
        'title',
        'image',
        'contact',
        'location',
        'social_links',
        'bio',
        'summary',
        'slug'
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
