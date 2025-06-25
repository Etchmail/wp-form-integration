<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $fillable = ['name', 'reseller_id'];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }
}
