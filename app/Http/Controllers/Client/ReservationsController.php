<?php

namespace App\Http\Controllers\Client;

use App\Enums\CarStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReservationsController extends Controller
{
    public function index(Request $request)
    {

        $reservations = Reservation::where('user_id', auth()->user()->id)
            ->with(['car', 'review'])
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return inertia('Client/Reservations/Index', [
            'reservations' => $reservations,
        ]);
    }

    public function show($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->load(['user', 'car', 'payments', 'review']);

        return inertia('Client/Reservations/Show', [
            'reservation' => $reservation,
            'statusMeta' => ReservationStatus::getMeta(),
            'paymentStatusMeta' => PaymentStatus::getMeta(),
        ]);
    }

    public function print($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->load(['user', 'car', 'payments']);

        $pdf = Pdf::loadView('admin.reservations.print', [
            'reservation' => $reservation,
            'statusMeta' => ReservationStatus::getMeta(),
            'paymentStatusMeta' => PaymentStatus::getMeta(),
            'currency' => config('app.currency_symbol'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($reservation->reservation_number . '.pdf');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        if (! $reservation->canBeCancelled()) {
            return back()->with('error', 'This reservation is not eligible for cancellation.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|in:Change of travel plans,Found alternative transportation,Trip postponed,Personal or medical emergency,Inclement weather / flight disruption,Other',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($reservation, $validated) {
            $notes = trim($validated['notes'] ?? '');
            $reasonText = $validated['reason'] . ($notes !== '' ? ': ' . $notes : '');

            $reservation->update([
                'status' => ReservationStatus::CANCELLED,
                'cancellation_reason' => $reasonText,
                'cancelled_at' => now(),
            ]);

            $reservation->load('payments');

            foreach ($reservation->payments as $payment) {
                if ($payment->status === PaymentStatus::COMPLETED) {
                    $refundRef = 'REF-' . strtoupper(Str::random(10));
                    $payment->update([
                        'status' => PaymentStatus::REFUNDED,
                        'refunded_amount' => $payment->amount,
                        'transaction_id' => ($payment->transaction_id ? $payment->transaction_id . ' | ' : '') . $refundRef,
                        'notes' => ($payment->notes ? $payment->notes . "\n" : '') . 'Full refund of ₹' . number_format($payment->amount) . ' processed automatically upon cancellation.',
                    ]);
                } elseif ($payment->status === PaymentStatus::PENDING) {
                    $payment->update([
                        'status' => PaymentStatus::CANCELLED,
                    ]);
                }
            }
        });

        return back()->with('success', "Booking #{$reservation->reservation_number} has been cancelled. A full refund of ₹" . number_format($reservation->total_amount) . " has been initiated to your original payment method.");
    }

    public function checkExtension(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        if (! in_array($reservation->status, [ReservationStatus::CONFIRMED, ReservationStatus::ACTIVE])) {
            return response()->json(['available' => false, 'message' => 'This reservation cannot be extended.'], 422);
        }

        $validated = $request->validate([
            'additional_days' => 'required|integer|min:1|max:14',
        ]);

        $extraDays = (int) $validated['additional_days'];
        $currentEndDate = Carbon::parse($reservation->end_date)->startOfDay();
        $extensionStartDate = $currentEndDate->copy()->addDay();
        $newEndDate = $currentEndDate->copy()->addDays($extraDays);

        // Check if car has an overlapping reservation on extended days
        $hasConflict = Reservation::where('car_id', $reservation->car_id)
            ->where('id', '!=', $reservation->id)
            ->whereIn('status', [ReservationStatus::CONFIRMED, ReservationStatus::ACTIVE, ReservationStatus::PENDING])
            ->where(function ($query) use ($extensionStartDate, $newEndDate) {
                $query->where('start_date', '<=', $newEndDate->format('Y-m-d'))
                      ->where('end_date', '>=', $extensionStartDate->format('Y-m-d'));
            })
            ->exists();

        if ($hasConflict) {
            return response()->json([
                'available' => false,
                'message' => 'Vehicle is already reserved by another customer for these extended dates. Extension unavailable.',
                'new_end_date' => $newEndDate->format('Y-m-d'),
            ]);
        }

        $dailyRate = (float) $reservation->daily_rate;
        $extraBase = $dailyRate * $extraDays;

        $dailyExtrasRate = 0;
        if (is_array($reservation->extras)) {
            foreach ($reservation->extras as $extra) {
                if (($extra['type'] ?? '') === 'per_day') {
                    $dailyExtrasRate += (float) ($extra['rate'] ?? 0);
                }
            }
        }

        $extraDailyExtrasAmount = $dailyExtrasRate * $extraDays;
        $differentialSubtotal = $extraBase + $extraDailyExtrasAmount;
        $differentialTax = round($differentialSubtotal * 0.18, 2);
        $differentialTotal = $differentialSubtotal + $differentialTax;

        return response()->json([
            'available' => true,
            'extra_days' => $extraDays,
            'current_end_date' => $currentEndDate->format('Y-m-d'),
            'new_end_date' => $newEndDate->format('Y-m-d'),
            'daily_rate' => $dailyRate,
            'daily_extras_rate' => $dailyExtrasRate,
            'differential_base' => $extraBase,
            'differential_extras' => $extraDailyExtrasAmount,
            'differential_subtotal' => $differentialSubtotal,
            'differential_tax' => $differentialTax,
            'differential_total' => $differentialTotal,
        ]);
    }

    public function extend(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        if (! in_array($reservation->status, [ReservationStatus::CONFIRMED, ReservationStatus::ACTIVE])) {
            return back()->with('error', 'This reservation cannot be extended.');
        }

        $validated = $request->validate([
            'additional_days' => 'required|integer|min:1|max:14',
            'payment_method' => 'nullable|string|in:credit_card,upi,cash',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        $extraDays = (int) $validated['additional_days'];
        $currentEndDate = Carbon::parse($reservation->end_date)->startOfDay();
        $extensionStartDate = $currentEndDate->copy()->addDay();
        $newEndDate = $currentEndDate->copy()->addDays($extraDays);

        $hasConflict = Reservation::where('car_id', $reservation->car_id)
            ->where('id', '!=', $reservation->id)
            ->whereIn('status', [ReservationStatus::CONFIRMED, ReservationStatus::ACTIVE, ReservationStatus::PENDING])
            ->where(function ($query) use ($extensionStartDate, $newEndDate) {
                $query->where('start_date', '<=', $newEndDate->format('Y-m-d'))
                      ->where('end_date', '>=', $extensionStartDate->format('Y-m-d'));
            })
            ->exists();

        if ($hasConflict) {
            return back()->with('error', 'Vehicle is no longer available for the requested extension dates.');
        }

        $dailyRate = (float) $reservation->daily_rate;
        $extraBase = $dailyRate * $extraDays;

        $dailyExtrasRate = 0;
        $updatedExtras = [];
        $newTotalDays = (int) $reservation->total_days + $extraDays;

        if (is_array($reservation->extras)) {
            foreach ($reservation->extras as $extra) {
                if (($extra['type'] ?? '') === 'per_day') {
                    $rate = (float) ($extra['rate'] ?? 0);
                    $dailyExtrasRate += $rate;
                    $updatedExtras[] = array_merge($extra, [
                        'days' => $newTotalDays,
                        'total' => round($rate * $newTotalDays, 2),
                    ]);
                } else {
                    $updatedExtras[] = $extra;
                }
            }
        }

        $extraDailyExtrasAmount = $dailyExtrasRate * $extraDays;
        $differentialSubtotal = $extraBase + $extraDailyExtrasAmount;
        $differentialTax = round($differentialSubtotal * 0.18, 2);
        $differentialTotal = $differentialSubtotal + $differentialTax;

        DB::transaction(function () use (
            $reservation,
            $newEndDate,
            $newTotalDays,
            $differentialSubtotal,
            $differentialTax,
            $differentialTotal,
            $updatedExtras,
            $extraDays,
            $validated
        ) {
            $reservation->update([
                'end_date' => $newEndDate->format('Y-m-d'),
                'total_days' => $newTotalDays,
                'subtotal' => (float) $reservation->subtotal + $differentialSubtotal,
                'tax_amount' => (float) $reservation->tax_amount + $differentialTax,
                'total_amount' => (float) $reservation->total_amount + $differentialTotal,
                'extras' => count($updatedExtras) > 0 ? $updatedExtras : $reservation->extras,
                'notes' => trim(($reservation->notes ? $reservation->notes . "\n" : '') . "Extended trip by +{$extraDays} days to " . $newEndDate->format('M d, Y') . " (Differential: ₹" . number_format($differentialTotal) . ")."),
            ]);

            $paymentMethod = PaymentMethod::tryFrom($validated['payment_method'] ?? 'credit_card') ?? PaymentMethod::CREDIT_CARD;
            $txnId = $validated['transaction_id'] ?? ('TXN-EXT-' . strtoupper(Str::random(8)));

            Payment::create([
                'reservation_id' => $reservation->id,
                'user_id' => auth()->id(),
                'amount' => $differentialTotal,
                'currency' => config('app.currency_code') ?: 'INR',
                'payment_method' => $paymentMethod,
                'status' => PaymentStatus::COMPLETED,
                'transaction_id' => $txnId,
                'processed_at' => now(),
                'notes' => "Trip extension differential (+{$extraDays} days).",
            ]);
        });

        return back()->with('success', "Reservation #{$reservation->reservation_number} successfully extended by {$extraDays} days to " . $newEndDate->format('M d, Y') . ".");
    }

    public function earlyReturn(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }

        if ($reservation->status !== ReservationStatus::ACTIVE) {
            return back()->with('error', 'Only active ongoing rentals can be returned early.');
        }

        $start = Carbon::parse($reservation->start_date)->startOfDay();
        $now = Carbon::now()->startOfDay();

        // Days elapsed so far, minimum 1 day
        $daysUsed = max(1, $start->diffInDays($now) + 1);
        $daysUsed = min($daysUsed, (int) $reservation->total_days);

        $unusedDays = max(0, (int) $reservation->total_days - $daysUsed);

        if ($unusedDays <= 0) {
            return back()->with('error', 'No unused rental days remain to issue an early return refund.');
        }

        $originalDailyTotal = (float) $reservation->total_amount / (float) $reservation->total_days;
        $refundAmount = round($originalDailyTotal * $unusedDays, 2);

        DB::transaction(function () use ($reservation, $daysUsed, $unusedDays, $refundAmount) {
            $refundRef = 'REF-EARLY-' . strtoupper(Str::random(8));

            $reservation->update([
                'status' => ReservationStatus::COMPLETED,
                'end_date' => Carbon::now()->format('Y-m-d'),
                'notes' => trim(($reservation->notes ? $reservation->notes . "\n" : '') . "Early Return executed on " . now()->format('M d, Y') . ". Trip shortened from {$reservation->total_days} to {$daysUsed} days. Prorated refund of ₹" . number_format($refundAmount) . " issued for {$unusedDays} unused days ({$refundRef})."),
            ]);

            if ($reservation->car) {
                $reservation->car->update([
                    'status' => CarStatus::CLEANING,
                ]);
            }

            Payment::create([
                'reservation_id' => $reservation->id,
                'user_id' => auth()->id(),
                'amount' => $refundAmount,
                'refunded_amount' => $refundAmount,
                'currency' => config('app.currency_code') ?: 'INR',
                'payment_method' => PaymentMethod::CREDIT_CARD,
                'status' => PaymentStatus::REFUNDED,
                'transaction_id' => $refundRef,
                'processed_at' => now(),
                'notes' => "Prorated refund for {$unusedDays} unused rental days.",
            ]);
        });

        return back()->with('success', "Early return processed. Reservation completed and a prorated refund of ₹" . number_format($refundAmount) . " has been issued to your original payment method.");
    }
}
