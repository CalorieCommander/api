<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nutrient extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'meal_id',
        'energy',
        'calories',
        'carbohydrates',
        'fats',
        'sugar',
        'fibres',
        'protein',
        'salt',
        'created_at',
        'updated_at',
    ];

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }
}
