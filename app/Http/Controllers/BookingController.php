<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Enums\ReservationStatus;
use App\Models\Car;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
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

        return inertia('Booking', [
            'car' => $car,
            'bookedRanges' => $bookedRanges,
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

            // calculations
            $subtotal   = $dailyRate * $days;
            $taxRate    = 0.07;
            $taxAmount  = $subtotal * $taxRate;
            $discount   = 0;
            $total      = $subtotal + $taxAmount - $discount;

            return Reservation::create([
                'car_id'          => $lockedCar->id,
                'user_id'         => Auth::id(),
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'pickup_location' => $request->pickup_location,
                'return_location' => $request->return_location,
                'total_days'      => $days,
                'daily_rate'      => $dailyRate,
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'discount_amount' => $discount,
                'total_amount'    => $total,
                'status'          => ReservationStatus::PENDING,
            ]);
        });

        return redirect()->route('booking.confirmation', $reservation);
    }


    public function confirmation(Reservation $reservation)
    {
        // Make sure user can only see their own reservations
        if ($reservation->user_id !== Auth::user()->id) {
            return redirect()->route('fleet');
        }

        return inertia('BookingConfirmation', [
            'reservation' => $reservation->load(['car', 'user']),
        ]);
    }
}
