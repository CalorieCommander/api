<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Date_meal extends Model
{
    use HasFactory;

    protected $table = 'dates_meals';
    protected $fillable = [
        'date_id',
        'meal_id',
        'amount',
        'grams',
        'created_at',
        'updated_at',
    ];

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }
    public function date(): BelongsTo
    {
        return $this->belongsTo(Date::class);
    }
}
