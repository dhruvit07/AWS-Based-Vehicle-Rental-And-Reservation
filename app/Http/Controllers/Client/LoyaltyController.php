<?php

namespace App\Http\Controllers\Client;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyController extends Controller
{
    /**
     * Display the client's VIP Loyalty & Rewards Hub.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure stats reflect current completed reservations
        $user->recalculateLoyaltyStats();

        $completedReservations = $user->reservations()
            ->where('status', ReservationStatus::COMPLETED)
            ->with('car:id,make,model,year,license_plate')
            ->latest('end_date')
            ->take(10)
            ->get();

        $lifetimeSavings = (float) $user->reservations()
            ->where('status', ReservationStatus::COMPLETED)
            ->sum('loyalty_discount_amount');

        $allTiers = [
            [
                'id' => 'bronze',
                'name' => 'Bronze Member',
                'badge' => 'Member',
                'discount' => '0% Off',
                'discount_percent' => 0,
                'requirement' => '0–2 Completed Trips',
                'description' => 'Entry-level access to the premium fleet with standard digital booking and 24/7 concierge.',
                'features' => [
                    ['text' => 'Full access to luxury & sports fleet', 'included' => true],
                    ['text' => '24/7 emergency roadside assistance', 'included' => true],
                    ['text' => 'Digital QR pickup voucher pass', 'included' => true],
                    ['text' => 'Automated checkout price discounts', 'included' => false],
                    ['text' => 'Priority vehicle dispatch radar beacon', 'included' => false],
                    ['text' => 'Zero-deductible insurance included', 'included' => false],
                ],
            ],
            [
                'id' => 'silver',
                'name' => 'Silver Explorer',
                'badge' => 'Silver 🥈',
                'discount' => '5% Off',
                'discount_percent' => 5,
                'requirement' => '3+ Trips or ₹25,000 Spend',
                'description' => 'For frequent road-trippers and city explorers. Enjoy immediate 5% savings on all bookings.',
                'features' => [
                    ['text' => 'Full access to luxury & sports fleet', 'included' => true],
                    ['text' => '24/7 emergency roadside assistance', 'included' => true],
                    ['text' => 'Digital QR pickup voucher pass', 'included' => true],
                    ['text' => '5% automatic checkout discount', 'included' => true],
                    ['text' => 'Verified priority customer support queue', 'included' => true],
                    ['text' => 'Priority vehicle dispatch radar beacon', 'included' => false],
                ],
            ],
            [
                'id' => 'gold',
                'name' => 'Gold Executive',
                'badge' => 'Gold 🌟',
                'discount' => '10% Off',
                'discount_percent' => 10,
                'requirement' => '6+ Trips or ₹60,000 Spend',
                'description' => 'Executive level mobility with double discount perks, priority hub handover, and add-on savings.',
                'features' => [
                    ['text' => 'Full access to luxury & sports fleet', 'included' => true],
                    ['text' => '24/7 emergency roadside assistance', 'included' => true],
                    ['text' => '10% automatic checkout discount', 'included' => true],
                    ['text' => 'Priority vehicle dispatch radar beacon', 'included' => true],
                    ['text' => '50% discount on all rental add-ons & extras', 'included' => true],
                    ['text' => 'Expedited handover queue at airport hubs', 'included' => true],
                ],
            ],
            [
                'id' => 'platinum',
                'name' => 'Platinum VIP',
                'badge' => 'Platinum 👑',
                'discount' => '15% Off',
                'discount_percent' => 15,
                'requirement' => '10+ Trips or ₹100,000 Spend',
                'description' => 'The ultimate VIP tier. 15% automatic discount, highest radar dispatch priority, and zero deductible coverage.',
                'features' => [
                    ['text' => '15% automatic VIP discount on all bookings', 'included' => true],
                    ['text' => 'VIP radar beacon & top priority hub release', 'included' => true],
                    ['text' => 'Zero-deductible insurance cover included', 'included' => true],
                    ['text' => 'Dedicated personal fleet concierge desk', 'included' => true],
                    ['text' => 'Complimentary additional driver authorization', 'included' => true],
                    ['text' => 'Guaranteed latest-model vehicle allocation', 'included' => true],
                ],
            ],
        ];

        return Inertia::render('Client/Loyalty/Index', [
            'tierMeta' => $user->loyalty_tier_meta,
            'stats' => [
                'completed_trips' => (int) $user->completed_trips_count,
                'lifetime_spend' => (float) $user->lifetime_spend,
                'lifetime_savings' => $lifetimeSavings,
            ],
            'recentTrips' => $completedReservations,
            'allTiers' => $allTiers,
        ]);
    }

    /**
     * Simulate a loyalty tier for viva presentation and evaluation demonstrations.
     */
    public function simulateTier(Request $request)
    {
        $validated = $request->validate([
            'tier' => ['required', 'string', 'in:bronze,silver,gold,platinum'],
        ]);

        $user = $request->user();
        $user->setSimulatedTier($validated['tier']);

        $meta = $user->loyalty_tier_meta;

        return back()->with('success', "Simulated Tier activated: {$meta['label']}! You now receive {$meta['discount_percent']}% off vehicle checkout.");
    }
}
