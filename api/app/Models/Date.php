<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Date extends Model
{
    use HasFactory;

    protected $table = 'dates';
    protected $fillable = [
        'user_id',
        'user_weight',
        'date',
        'created_at',
        'updated_at',
    ];
    
    public function date_meals(): HasMany
    {
        return $this->HasMany(Date_meal::class);
    }
    public function date_activities(): HasMany
    {
        return $this->HasMany(Date_activity::class);
    }
}
