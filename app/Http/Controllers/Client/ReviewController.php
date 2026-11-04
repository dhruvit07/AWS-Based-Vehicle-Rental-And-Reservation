<?php

namespace App\Http\Controllers\Client;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\CarReview;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Store a newly created review for a completed reservation.
     */
    public function store(Request $request, Reservation $reservation): RedirectResponse
    {
        // 1. Authorize user owns the reservation
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Unauthorized to review this reservation.');
        }

        // 2. Validate reservation is completed
        if ($reservation->status !== ReservationStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'reservation' => 'Only completed reservations can be reviewed.',
            ]);
        }

        // 3. Ensure reservation hasn't already been reviewed
        if ($reservation->review()->exists()) {
            throw ValidationException::withMessages([
                'reservation' => 'A review has already been submitted for this reservation.',
            ]);
        }

        // 4. Validate input
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:100',
            'comment' => 'required|string|min:5|max:1000',
        ]);

        // 5. Create the review
        CarReview::create([
            'car_id' => $reservation->car_id,
            'user_id' => auth()->id(),
            'reservation_id' => $reservation->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'is_published' => true,
        ]);

        return back()->with('success', 'Thank you! Your review has been submitted successfully.');
    }
}
