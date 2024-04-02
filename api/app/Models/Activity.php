<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'calories_per_km',
        'created_at',
        'updated_at',
    ];

    public function date_activity(): HasMany
    {
        return $this->hasMany(Date_activity::class);
    }
}
