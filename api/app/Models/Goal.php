<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    use HasFactory;
    protected $table = 'goals';
    protected $fillable = [
        'user_id',
        'date',
        'weight_goal',
        'daily_calories',
        'created_at',
        'updated_at',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
