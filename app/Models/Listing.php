<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Listing extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'kelurahan_id',
        'title',
        'description',
        'price',
        'is_negotiable',
        'condition',
        'condition_note',
        'status',
        'view_count',
        'material',
        'size',
        'usage_duration',
        'quantity',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:0',
            'is_negotiable' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(WilayahKelurahan::class, 'kelurahan_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('sort_order');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ListingPhoto::class)->orderBy('sort_order');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
