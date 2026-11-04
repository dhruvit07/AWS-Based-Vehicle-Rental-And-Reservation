<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'COLLEGE20',
                'type' => 'percentage',
                'value' => 20.00,
                'min_spend' => 1000.00,
                'max_discount' => 2000.00,
                'is_active' => true,
                'expires_at' => null,
            ],
            [
                'code' => 'FLAT500',
                'type' => 'fixed',
                'value' => 500.00,
                'min_spend' => 2000.00,
                'max_discount' => null,
                'is_active' => true,
                'expires_at' => null,
            ],
            [
                'code' => 'FIRSTDRIVE',
                'type' => 'percentage',
                'value' => 15.00,
                'min_spend' => 500.00,
                'max_discount' => null,
                'is_active' => true,
                'expires_at' => null,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}
