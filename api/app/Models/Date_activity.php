<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Date_activity extends Model
{
    use HasFactory;

    protected $table = 'dates_activities';
    protected $fillable = [
        'date_id',
        'activity_id',
        'kilometers',
        'created_at',
        'updated_at',
    ];
    public function date(): BelongsTo
    {
        return $this->belongsTo(Date::class);
    }
    public function activity(): HasOne
    {
        return $this->hasOne(Activity::class);
    }
}
