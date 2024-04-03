<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meal extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'name',
        'brand',
        'calories_per_gram',
        'nutrition_grade',
        'created_at',
        'updated_at',
    ];

    public function date_meal(): HasMany
    {
        return $this->hasMany(Date_meal::class);
    }
    public function nutrient(): HasOne
    {
        return $this->hasOne(Nutrient::class);
    }
}
