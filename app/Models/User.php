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
        'loyalty_tier',
        'lifetime_spend',
        'completed_trips_count',
        'tier_updated_at',
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
        'loyalty_tier_meta',
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
            'lifetime_spend' => 'decimal:2',
            'completed_trips_count' => 'integer',
            'tier_updated_at' => 'datetime',
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

    /**
     * Get vehicle inspections conducted by this administrator.
     */
    public function conductedInspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class, 'inspector_id');
    }

    /**
     * Get the discount rate corresponding to the user's loyalty tier.
     */
    public function getLoyaltyDiscountRateAttribute(): float
    {
        return match ($this->loyalty_tier) {
            'platinum' => 0.15,
            'gold'     => 0.10,
            'silver'   => 0.05,
            default    => 0.00,
        };
    }

    /**
     * Get structured metadata for the user's loyalty tier.
     */
    public function getLoyaltyTierMetaAttribute(): array
    {
        $tier = $this->loyalty_tier ?: 'bronze';
        $trips = (int) $this->completed_trips_count;
        $spend = (float) $this->lifetime_spend;

        $tierConfigs = [
            'bronze' => [
                'name' => 'bronze',
                'label' => 'Bronze Member',
                'badge' => 'Member',
                'icon' => 'Shield',
                'discount_percent' => 0,
                'discount_rate' => 0.00,
                'next_tier' => 'silver',
                'next_tier_label' => 'Silver Explorer',
                'target_trips' => 3,
                'target_spend' => 25000.00,
                'color' => 'slate',
                'perks' => [
                    'Standard booking access',
                    '24/7 roadside concierge',
                    'Digital QR vehicle voucher pass',
                ],
            ],
            'silver' => [
                'name' => 'silver',
                'label' => 'Silver Explorer',
                'badge' => 'Silver 🥈',
                'icon' => 'Award',
                'discount_percent' => 5,
                'discount_rate' => 0.05,
                'next_tier' => 'gold',
                'next_tier_label' => 'Gold Executive',
                'target_trips' => 6,
                'target_spend' => 60000.00,
                'color' => 'sky',
                'perks' => [
                    '5% automatic discount on all vehicle rentals',
                    'Verified priority customer support queue',
                    'Complimentary child safety seat add-on',
                ],
            ],
            'gold' => [
                'name' => 'gold',
                'label' => 'Gold Executive',
                'badge' => 'Gold 🌟',
                'icon' => 'Sparkles',
                'discount_percent' => 10,
                'discount_rate' => 0.10,
                'next_tier' => 'platinum',
                'next_tier_label' => 'Platinum VIP',
                'target_trips' => 10,
                'target_spend' => 100000.00,
                'color' => 'amber',
                'perks' => [
                    '10% automatic discount on all vehicle rentals',
                    'Priority handover queue at airport hubs',
                    '50% discount on all premium rental extras',
                ],
            ],
            'platinum' => [
                'name' => 'platinum',
                'label' => 'Platinum VIP',
                'badge' => 'Platinum 👑',
                'icon' => 'Crown',
                'discount_percent' => 15,
                'discount_rate' => 0.15,
                'next_tier' => null,
                'next_tier_label' => null,
                'target_trips' => 10,
                'target_spend' => 100000.00,
                'color' => 'purple',
                'perks' => [
                    '15% automatic VIP discount on all vehicle rentals',
                    'VIP radar priority beacon & expedited dispatch',
                    'Zero-deductible comprehensive insurance cover included',
                    'Dedicated personal fleet concierge desk',
                ],
            ],
        ];

        $current = $tierConfigs[$tier] ?? $tierConfigs['bronze'];

        $tripsToNext = 0;
        $spendToNext = 0.00;
        $progress = 100;

        if ($current['next_tier']) {
            $targetTrips = $current['target_trips'];
            $targetSpend = $current['target_spend'];

            $tripsToNext = max(0, $targetTrips - $trips);
            $spendToNext = max(0.00, round($targetSpend - $spend, 2));

            $tripProgress = min(100, round(($trips / $targetTrips) * 100));
            $spendProgress = min(100, round(($spend / $targetSpend) * 100));
            $progress = (int) max($tripProgress, $spendProgress);
        }

        return array_merge($current, [
            'trips_to_next' => $tripsToNext,
            'spend_to_next' => $spendToNext,
            'progress_percent' => $progress,
            'completed_trips' => $trips,
            'lifetime_spend' => $spend,
        ]);
    }

    /**
     * Recalculate completed trips and lifetime spend from the database,
     * and promote loyalty tier accordingly.
     */
    public function recalculateLoyaltyStats(): void
    {
        $completedReservations = $this->reservations()
            ->where('status', \App\Enums\ReservationStatus::COMPLETED)
            ->get();

        $tripsCount = $completedReservations->count();
        $totalSpend = (float) $completedReservations->sum('total_amount');

        $this->completed_trips_count = $tripsCount;
        $this->lifetime_spend = $totalSpend;

        // Auto-promote if organic thresholds met (never demote a manually simulated tier)
        $currentTier = $this->loyalty_tier ?: 'bronze';
        $newTier = 'bronze';

        if ($tripsCount >= 10 || $totalSpend >= 100000.00) {
            $newTier = 'platinum';
        } elseif ($tripsCount >= 6 || $totalSpend >= 60000.00) {
            $newTier = 'gold';
        } elseif ($tripsCount >= 3 || $totalSpend >= 25000.00) {
            $newTier = 'silver';
        }

        $tierRanks = ['bronze' => 0, 'silver' => 1, 'gold' => 2, 'platinum' => 3];
        if (($tierRanks[$newTier] ?? 0) > ($tierRanks[$currentTier] ?? 0)) {
            $this->loyalty_tier = $newTier;
            $this->tier_updated_at = now();
        }

        $this->save();
    }

    /**
     * Set a simulated tier for demonstration and viva presentation purposes.
     */
    public function setSimulatedTier(string $tier): void
    {
        if (in_array($tier, ['bronze', 'silver', 'gold', 'platinum'], true)) {
            $this->loyalty_tier = $tier;
            $this->tier_updated_at = now();
            $this->save();
        }
    }
}
