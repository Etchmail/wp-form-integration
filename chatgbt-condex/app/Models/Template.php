<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = ['name', 'structure_json'];

    protected $casts = [
        'structure_json' => 'array',
    ];

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }
}
