<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // 8.1.2 · Peran akun. Sengaja tidak ada di $fillable agar tidak bisa diisi dari form pendaftaran/profil.
    public const PENGGUNA = 'pengguna';
    public const PENGEPUL = 'pengepul';
    public const ADMIN = 'admin';

    public const PERAN = [
        self::PENGGUNA => 'Pengguna',
        self::PENGEPUL => 'Pengepul',
        self::ADMIN    => 'Admin',
    ];

    protected $attributes = [
        'role' => self::PENGGUNA,
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // --- Relasi ---

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function mitra(): HasOne
    {
        return $this->hasOne(MitraPengepul::class);
    }

    // --- Peran ---

    public function hasRole(string ...$peran): bool
    {
        return in_array($this->role, $peran, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ADMIN);
    }

    public function isPengepul(): bool
    {
        return $this->hasRole(self::PENGEPUL);
    }

    /** Halaman awal setelah login sesuai peran. */
    public function beranda(): string
    {
        return match ($this->role) {
            self::ADMIN    => route('admin.mitra.index'),
            self::PENGEPUL => route('mitra.pengaturan'),
            default        => route('account'),
        };
    }

    // --- Helper Profil & Avatar ---

    /**
     * Ambil inisial nama pengguna (maksimal 2 karakter huruf kapital).
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name ?? ''));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_substr($word, 0, 1);
        }

        return mb_strtoupper($initials ?: 'U');
    }

    /**
     * Ambil URL publik avatar pengguna jika ada.
     */
    public function avatarUrl(): ?string
    {
        return $this->profile?->avatar ? asset('storage/' . $this->profile->avatar) : null;
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function buyerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'buyer_id');
    }

    public function sellerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'seller_id');
    }

    public function buyerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function givenReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function orderSetors(): HasMany
    {
        return $this->hasMany(OrderSetor::class);
    }
}
