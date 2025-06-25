<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reseller extends Model
{
    protected $fillable = ['name'];

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
