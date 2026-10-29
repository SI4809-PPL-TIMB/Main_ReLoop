<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'kelurahan_id',
        'latitude',
        'longitude',
        'patokan',
        'avatar',
        'bio',
        'avg_rating',
        'total_sold',
        'streak_count',
        'streak_last_at',
        'level',
        'total_points',
    ];

    protected function casts(): array
    {
        return [
            'avg_rating'     => 'decimal:2',
            'latitude'       => 'float',
            'longitude'      => 'float',
            'streak_last_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(WilayahKelurahan::class, 'kelurahan_id');
    }
}
