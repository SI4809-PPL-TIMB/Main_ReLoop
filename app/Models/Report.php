<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const TYPE_LISTING = 'listing';
    public const TYPE_USER = 'user';
    public const TYPE_ORDER = 'order';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REJECTED = 'rejected';

    public const ACTION_DEACTIVATE_LISTING = 'deactivate_listing';
    public const ACTION_DEACTIVATE_USER = 'deactivate_user';
    public const ACTION_PAYOUT_SELLER = 'payout_seller';
    public const ACTION_REFUND_BUYER = 'refund_buyer';
    public const ACTION_DISMISSED = 'dismissed';

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'action_taken',
        'admin_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Dapatkan model entitas yang dilaporkan.
     */
    public function getTargetAttribute(): Model|null
    {
        return match ($this->reportable_type) {
            self::TYPE_LISTING => Listing::find($this->reportable_id),
            self::TYPE_USER    => User::find($this->reportable_id),
            self::TYPE_ORDER   => Order::find($this->reportable_id),
            default            => null,
        };
    }
}
