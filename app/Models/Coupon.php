<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend',
        'max_discount',
        'is_active',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Calculate discount amount for a given subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        if ($this->type === 'percentage') {
            $discount = ($subtotal * (float) $this->value) / 100;
            if ($this->max_discount !== null && (float) $this->max_discount > 0) {
                $discount = min($discount, (float) $this->max_discount);
            }
        } else {
            $discount = (float) $this->value;
        }

        $discount = min($discount, $subtotal);

        return round($discount, 2);
    }

    /**
     * Check if coupon is valid for a given subtotal.
     *
     * @return array{valid: bool, message: string}
     */
    public function validateFor(float $subtotal): array
    {
        if (! $this->is_active) {
            return [
                'valid' => false,
                'message' => 'This promo code is no longer active.',
            ];
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return [
                'valid' => false,
                'message' => 'This promo code has expired.',
            ];
        }

        if ((float) $this->min_spend > 0 && $subtotal < (float) $this->min_spend) {
            return [
                'valid' => false,
                'message' => 'Minimum order spend of ₹' . number_format((float) $this->min_spend, 2) . ' required to apply this code.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Promo code applied successfully!',
        ];
    }
}
