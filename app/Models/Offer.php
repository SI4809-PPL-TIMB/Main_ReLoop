<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    protected $fillable = [
        'conversation_id',
        'offerer_id',
        'amount',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:0',
            'expires_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function offerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offerer_id');
    }
}
