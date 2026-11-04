<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewsController extends Controller
{
    /**
     * Display a listing of customer reviews with filtering & stats.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $rating = $request->input('rating');

        $query = CarReview::with(['car', 'user', 'reservation'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('car', function ($cq) use ($search) {
                        $cq->where('make', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%");
                    });
            });
        }

        if ($rating) {
            $query->where('rating', (int) $rating);
        }

        $reviews = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => CarReview::count(),
            'average' => round((float) (CarReview::avg('rating') ?: 0), 1),
            'five_star' => CarReview::where('rating', 5)->count(),
            'four_star' => CarReview::where('rating', 4)->count(),
            'three_star_below' => CarReview::where('rating', '<=', 3)->count(),
        ];

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => [
                'search' => $search,
                'rating' => $rating,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(CarReview $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Customer review deleted successfully.');
    }
}
