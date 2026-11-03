<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

/** Hanya pemilik yang boleh mengubah, menghapus, dan menandai terjual (FR-04). */
class ListingPolicy
{
    private function pemilik(User $user, Listing $l): bool
    {
        return $user->id === $l->user_id && $l->status !== Listing::DIHAPUS;
    }

    public function update(User $user, Listing $l): bool
    {
        return $this->pemilik($user, $l) && $l->status !== Listing::TERJUAL;
    }

    public function delete(User $user, Listing $l): bool
    {
        return $this->pemilik($user, $l) && $l->status !== Listing::DIPESAN;
    }

    public function markSold(User $user, Listing $l): bool
    {
        return $this->pemilik($user, $l) && in_array($l->status, [Listing::AKTIF, Listing::DRAFT], true);
    }
}
