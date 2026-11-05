<?php

namespace App\Http\Controllers\Client;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
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
}
