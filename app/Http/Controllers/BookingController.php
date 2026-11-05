<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public const EXTRAS_CATALOG = [
        'insurance' => [
            'id' => 'insurance',
            'name' => 'Zero-Deductible Insurance',
            'rate' => 499.00,
            'type' => 'per_day',
            'description' => 'Complete peace of mind with zero excess on accidental damages',
        ],
        'gps' => [
            'id' => 'gps',
            'name' => 'GPS Navigation System',
            'rate' => 149.00,
            'type' => 'per_day',
            'description' => 'High-precision standalone satellite navigation with offline maps',
        ],
        'child_seat' => [
            'id' => 'child_seat',
            'name' => 'Child Safety Seat',
            'rate' => 199.00,
            'type' => 'per_day',
            'description' => 'Certified multi-age child restraint booster seat',
        ],
        'additional_driver' => [
            'id' => 'additional_driver',
            'name' => 'Additional Driver',
            'rate' => 499.00,
            'type' => 'fixed',
            'description' => 'Authorize a second verified driver for the entire trip',
        ],
    ];

    public function show(Car $car)
    {
        // Check if car is available for booking
        if ($car->status !== CarStatus::AVAILABLE) {
            return redirect()->route('fleet')->with('error', 'This car is not available for booking.');
        }

        $bookedRanges = $car->reservations()
            ->where(function ($q) {
                $q->whereIn('status', [ReservationStatus::CONFIRMED, ReservationStatus::ACTIVE])
                    ->orWhere(fn ($p) => $p->where('status', ReservationStatus::PENDING)->where('created_at', '>=', now()->subMinutes(30)));
            })
            ->get(['start_date', 'end_date'])
            ->map(fn ($r) => [
                'from' => $r->start_date->format('Y-m-d'),
                'to'   => $r->end_date->format('Y-m-d'),
            ])
            ->values();

        // Load published reviews with author details
        $reviews = $car->reviews()
            ->where('is_published', true)
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(function ($review) {
                $nameParts = explode(' ', trim($review->user?->name ?? 'Verified Renter'));
                $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                return [
                    'id' => $review->id,
                    'rating' => (int) $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at?->toISOString(),
                    'formatted_date' => $review->created_at ? $review->created_at->diffForHumans() : '',
                    'user' => [
                        'name' => $review->user?->name ?? 'Verified Renter',
                        'initials' => $initials ?: 'VR',
                    ],
                ];
            });

        // Check if authenticated client has an eligible completed reservation to review
        $eligibleReservation = null;
        if (Auth::check()) {
            $eligibleReservation = Reservation::where('user_id', Auth::id())
                ->where('car_id', $car->id)
                ->where('status', ReservationStatus::COMPLETED)
                ->whereDoesntHave('review')
                ->latest()
                ->first(['id', 'reservation_number', 'start_date', 'end_date']);
        }

        return inertia('Booking', [
            'car' => $car,
            'bookedRanges' => $bookedRanges,
            'extrasCatalog' => array_values(self::EXTRAS_CATALOG),
            'reviews' => $reviews,
            'ratingDistribution' => $car->rating_distribution,
            'eligibleReservation' => $eligibleReservation,
            'loyaltyTier' => Auth::check() ? Auth::user()->loyalty_tier_meta : null,
        ]);
    }

    /**
     * Validate a promo code against a cart subtotal.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $code = strtoupper(trim((string) $request->input('code', '')));
        $subtotal = floatval($request->input('subtotal', 0));

        if (empty($code)) {
            return response()->json([
                'valid' => false,
                'message' => 'Please enter a promo code.',
            ]);
        }

        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or inactive promo code.',
            ]);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'This promo code has expired.',
            ]);
        }

        if ((float) $coupon->min_spend > 0 && $subtotal < (float) $coupon->min_spend) {
            return response()->json([
                'valid' => false,
                'message' => 'Minimum order spend of ₹' . number_format((float) $coupon->min_spend, 2) . ' required to use this code.',
            ]);
        }

        $discount = $coupon->calculateDiscount($subtotal);

        $message = $coupon->type === 'percentage'
            ? "{$coupon->value}% discount applied (-₹" . number_format($discount, 2) . ")"
            : "₹" . number_format($discount, 2) . " discount applied";

        return response()->json([
            'valid' => true,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'discount_amount' => $discount,
            'message' => $message,
        ]);
    }

    public function book(Car $car, Request $request)
    {
        // check car is available for booking
        if ($car->status !== CarStatus::AVAILABLE) {
            return redirect()->route('fleet')->with('error', 'This car is not available for booking.');
        }

        // check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to book a car.');
        }

        // form validation
        $request->validate([
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'pickup_location'  => 'required|string|max:255',
            'return_location'  => 'required|string|max:255',
            'extras'           => 'nullable|array',
            'coupon_code'      => 'nullable|string|max:50',
            'payment_method'   => 'nullable|string|in:credit_card,upi,cash',
            'transaction_id'   => 'nullable|string|max:255',
        ]);

        $reservation = DB::transaction(function () use ($car, $request) {
            $lockedCar = Car::where('id', $car->id)->lockForUpdate()->firstOrFail();

            if (! $lockedCar->isAvailable($request->start_date, $request->end_date)) {
                throw ValidationException::withMessages([
                    'start_date' => 'This vehicle is already reserved for the selected dates.',
                ]);
            }

            // convert dates to Carbon
            $startDate = Carbon::parse($request->start_date);
            $endDate   = Carbon::parse($request->end_date);

            // calculate days (always at least 1)
            $days = max(1, $startDate->diffInDays($endDate));

            // ensure daily rate is positive
            $dailyRate = abs($lockedCar->price_per_day);
            $carBase   = $dailyRate * $days;

            // process extras
            $selectedExtraIds = (array) $request->input('extras', []);
            $frozenExtras = [];
            $extrasTotal = 0.0;

            foreach ($selectedExtraIds as $extraId) {
                if (isset(self::EXTRAS_CATALOG[$extraId])) {
                    $item = self::EXTRAS_CATALOG[$extraId];
                    $itemTotal = $item['type'] === 'per_day' ? $item['rate'] * $days : $item['rate'];
                    $frozenExtras[] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'rate' => (float) $item['rate'],
                        'type' => $item['type'],
                        'days' => $item['type'] === 'per_day' ? $days : null,
                        'total' => round($itemTotal, 2),
                    ];
                    $extrasTotal += $itemTotal;
                }
            }
            $extrasTotal = round($extrasTotal, 2);
            $subtotal    = round($carBase + $extrasTotal, 2);

            // Loyalty Tier discount calculation
            $loyaltyDiscount = 0.0;
            $appliedTier = null;
            $user = Auth::user();
            if ($user && $user->loyalty_discount_rate > 0) {
                $loyaltyDiscount = round($subtotal * (float) $user->loyalty_discount_rate, 2);
                $appliedTier = $user->loyalty_tier;
            }

            // coupon discount calculation
            $discount = 0.0;
            $appliedCouponCode = null;
            if ($request->filled('coupon_code')) {
                $code = strtoupper(trim((string) $request->input('coupon_code')));
                $coupon = Coupon::where('code', $code)
                    ->where('is_active', true)
                    ->first();

                if ($coupon && (! $coupon->expires_at || ! $coupon->expires_at->isPast())) {
                    if ((float) $coupon->min_spend <= 0 || $subtotal >= (float) $coupon->min_spend) {
                        $discount = $coupon->calculateDiscount($subtotal);
                        $appliedCouponCode = $coupon->code;
                    }
                }
            }
            $discount = round($discount, 2);

            $taxableAmount = max(0, $subtotal - $loyaltyDiscount - $discount);
            $taxRate       = 0.07;
            $taxAmount     = round($taxableAmount * $taxRate, 2);
            $total         = round($taxableAmount + $taxAmount, 2);

            $newReservation = Reservation::create([
                'car_id'                  => $lockedCar->id,
                'user_id'                 => Auth::id(),
                'start_date'              => $startDate,
                'end_date'                => $endDate,
                'pickup_location'         => $request->pickup_location,
                'return_location'         => $request->return_location,
                'extras'                  => !empty($frozenExtras) ? $frozenExtras : null,
                'total_days'              => $days,
                'daily_rate'              => $dailyRate,
                'subtotal'                => $subtotal,
                'tax_amount'              => $taxAmount,
                'discount_amount'         => $discount,
                'loyalty_discount_amount' => $loyaltyDiscount,
                'applied_loyalty_tier'    => $appliedTier,
                'coupon_code'             => $appliedCouponCode,
                'total_amount'            => $total,
                'status'                  => ReservationStatus::CONFIRMED,
            ]);

            // Create completed payment record
            $paymentMethod = PaymentMethod::tryFrom($request->input('payment_method', 'credit_card')) ?? PaymentMethod::CREDIT_CARD;
            $transactionId = $request->filled('transaction_id')
                ? (string) $request->input('transaction_id')
                : ('TXN-' . strtoupper(Str::random(10)));

            Payment::create([
                'reservation_id' => $newReservation->id,
                'user_id'        => Auth::id(),
                'amount'         => $total,
                'currency'       => config('app.currency_code') ?: 'INR',
                'payment_method' => $paymentMethod,
                'status'         => PaymentStatus::COMPLETED,
                'transaction_id' => $transactionId,
                'processed_at'   => now(),
            ]);

            return $newReservation;
        });

        return redirect()->route('booking.confirmation', $reservation);
    }

    public function confirmation(Reservation $reservation)
    {
        // Make sure user can only see their own reservations
        if ($reservation->user_id !== Auth::id()) {
            return redirect()->route('fleet');
        }

        return inertia('BookingConfirmation', [
            'reservation' => $reservation->load(['car', 'user', 'payments']),
        ]);
    }

    public function agreement(Reservation $reservation)
    {
        $user = Auth::user();
        if ($reservation->user_id !== $user->id && $user->role !== UserRole::ADMIN) {
            abort(403, 'Unauthorized to view this rental agreement.');
        }

        $reservation->load(['car', 'user', 'payments']);

        $verificationUrl = route('voucher.verify', ['reservation_number' => $reservation->reservation_number]);
        $securityHash = strtoupper(substr(hash('sha256', $reservation->reservation_number . '|' . ($reservation->created_at?->timestamp ?? time()) . '|RRC_VERIFIED'), 0, 16));

        return inertia('RentalAgreement', [
            'reservation' => $reservation,
            'verificationUrl' => $verificationUrl,
            'securityHash' => $securityHash,
            'statusMeta' => ReservationStatus::getMeta(),
            'paymentStatusMeta' => PaymentStatus::getMeta(),
            'extrasCatalog' => self::EXTRAS_CATALOG,
            'pdiChecklist' => [
                'starting_odometer' => $reservation->car->mileage ?? 14250,
                'fuel_level' => '100% (Full to Full)',
                'cleanliness' => 'Certified Clean & Sanitized',
                'spare_wheel_kit' => 'Present & Inspected',
                'inspector_name' => 'Rajesh Sharma',
                'inspector_badge' => 'RRC-INSP-4029',
                'inspected_at' => $reservation->created_at ? $reservation->created_at->format('M d, Y H:i') : now()->format('M d, Y H:i'),
                'zones' => [
                    'front' => ['label' => 'Front Bumper & Headlights', 'status' => 'Clear', 'notes' => 'No scratches or chips'],
                    'rear' => ['label' => 'Rear Bumper & Tail Lamps', 'status' => 'Clear', 'notes' => 'Factory condition verified'],
                    'left' => ['label' => 'Left Flank & Doors', 'status' => 'Clear', 'notes' => 'Clear surface, clean alloys'],
                    'right' => ['label' => 'Right Flank & Mirrors', 'status' => 'Clear', 'notes' => 'Glass & sensors intact'],
                ],
            ],
        ]);
    }

    public function verifyVoucher(string $reservation_number)
    {
        $reservation = Reservation::where('reservation_number', $reservation_number)
            ->with(['car', 'user', 'payments'])
            ->first();

        if (! $reservation) {
            return inertia('VoucherVerification', [
                'found' => false,
                'reservationNumber' => $reservation_number,
                'reservation' => null,
                'securityHash' => null,
                'verifiedAt' => now()->format('M d, Y H:i:s T'),
                'statusMeta' => ReservationStatus::getMeta(),
                'paymentStatusMeta' => PaymentStatus::getMeta(),
                'isAuthorized' => false,
            ]);
        }

        $securityHash = strtoupper(substr(hash('sha256', $reservation->reservation_number . '|' . ($reservation->created_at?->timestamp ?? time()) . '|RRC_VERIFIED'), 0, 16));

        $isAuthorized = in_array($reservation->status, [ReservationStatus::CONFIRMED, ReservationStatus::ACTIVE]);

        return inertia('VoucherVerification', [
            'found' => true,
            'reservationNumber' => $reservation_number,
            'reservation' => $reservation,
            'securityHash' => $securityHash,
            'verifiedAt' => now()->format('M d, Y H:i:s T'),
            'statusMeta' => ReservationStatus::getMeta(),
            'paymentStatusMeta' => PaymentStatus::getMeta(),
            'isAuthorized' => $isAuthorized,
        ]);
    }
}
