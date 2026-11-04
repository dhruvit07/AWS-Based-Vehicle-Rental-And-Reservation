<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Enums\UserRole;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'kyc_status',
        'license_number',
        'license_expiry',
        'license_front_image',
        'license_back_image',
        'kyc_rejection_reason',
        'kyc_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'license_front_url',
        'license_back_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'license_expiry' => 'date',
            'kyc_verified_at' => 'datetime',
        ];
    }

    /**
     * Check if the user's KYC verification is approved.
     */
    public function isKycVerified(): bool
    {
        return $this->kyc_status === 'verified';
    }

    /**
     * Check if the user's KYC verification is pending review.
     */
    public function isKycPending(): bool
    {
        return $this->kyc_status === 'pending';
    }

    /**
     * Get public URL for front license image.
     */
    public function getLicenseFrontUrlAttribute(): ?string
    {
        return $this->license_front_image ? Storage::disk('public')->url($this->license_front_image) : null;
    }

    /**
     * Get public URL for back license image.
     */
    public function getLicenseBackUrlAttribute(): ?string
    {
        return $this->license_back_image ? Storage::disk('public')->url($this->license_back_image) : null;
    }

    /**
     * Get the reservations for the user.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Get the payments for the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the reviews authored by the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(CarReview::class);
    }
}
