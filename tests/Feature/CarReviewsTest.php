<?php

use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\CarReview;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createReviewTestCar(array $attributes = []): Car
{
    return Car::create(array_merge([
        'make' => 'BMW',
        'model' => 'M3',
        'year' => 2024,
        'license_plate' => 'REV-' . fake()->unique()->numerify('####'),
        'color' => CarColor::BLACK,
        'price_per_day' => 150.00,
        'mileage' => 10000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ], $attributes));
}

function createReviewTestReservation(User $user, Car $car, ReservationStatus $status = ReservationStatus::COMPLETED): Reservation
{
    return Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-05',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 4,
        'daily_rate' => $car->price_per_day,
        'subtotal' => 600.00,
        'tax_amount' => 42.00,
        'discount_amount' => 0,
        'total_amount' => 642.00,
        'status' => $status,
    ]);
}

it('allows client with completed reservation to submit a review', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();
    $reservation = createReviewTestReservation($client, $car, ReservationStatus::COMPLETED);

    $response = $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $reservation), [
            'rating' => 5,
            'title' => 'Absolute blast to drive!',
            'comment' => 'The car was impeccably clean, responsive, and handover took less than two minutes.',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('car_reviews', [
        'car_id' => $car->id,
        'user_id' => $client->id,
        'reservation_id' => $reservation->id,
        'rating' => 5,
        'title' => 'Absolute blast to drive!',
        'is_published' => true,
    ]);
});

it('prevents user who does not own the reservation from reviewing', function () {
    $owner = User::factory()->create(['role' => UserRole::CLIENT]);
    $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();
    $reservation = createReviewTestReservation($owner, $car, ReservationStatus::COMPLETED);

    $response = $this->actingAs($otherClient)
        ->post(route('client.reservations.reviews.store', $reservation), [
            'rating' => 5,
            'comment' => 'Sneaking in an unauthorized review.',
        ]);

    $response->assertForbidden();
    $this->assertDatabaseEmpty('car_reviews');
});

it('prevents reviewing pending or cancelled reservations', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();

    $pendingRes = createReviewTestReservation($client, $car, ReservationStatus::PENDING);
    $cancelledRes = createReviewTestReservation($client, $car, ReservationStatus::CANCELLED);

    // Pending
    $responsePending = $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $pendingRes), [
            'rating' => 4,
            'comment' => 'Tried to review before pickup.',
        ]);
    $responsePending->assertSessionHasErrors('reservation');

    // Cancelled
    $responseCancelled = $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $cancelledRes), [
            'rating' => 2,
            'comment' => 'Tried to review after cancelling.',
        ]);
    $responseCancelled->assertSessionHasErrors('reservation');

    $this->assertDatabaseEmpty('car_reviews');
});

it('prevents reviewing the same reservation twice', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();
    $reservation = createReviewTestReservation($client, $car, ReservationStatus::COMPLETED);

    // First review succeeds
    $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $reservation), [
            'rating' => 5,
            'comment' => 'First review was fantastic!',
        ])
        ->assertSessionHasNoErrors();

    // Second review on same reservation must fail
    $secondAttempt = $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $reservation), [
            'rating' => 4,
            'comment' => 'Trying to submit a duplicate review.',
        ]);

    $secondAttempt->assertSessionHasErrors('reservation');
    expect(CarReview::where('reservation_id', $reservation->id)->count())->toBe(1);
});

it('validates rating is between 1 and 5 and comment is at least 5 chars', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();
    $reservation = createReviewTestReservation($client, $car, ReservationStatus::COMPLETED);

    // Rating 0
    $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $reservation), [
            'rating' => 0,
            'comment' => 'Good car overall.',
        ])
        ->assertSessionHasErrors('rating');

    // Rating 6
    $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $reservation), [
            'rating' => 6,
            'comment' => 'Over the top rating.',
        ])
        ->assertSessionHasErrors('rating');

    // Comment too short (< 5 chars)
    $this->actingAs($client)
        ->post(route('client.reservations.reviews.store', $reservation), [
            'rating' => 5,
            'comment' => 'Bad',
        ])
        ->assertSessionHasErrors('comment');
});

it('calculates average rating, reviews count, and rating distribution on Car model', function () {
    $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
    $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
    $client3 = User::factory()->create(['role' => UserRole::CLIENT]);

    $car = createReviewTestCar();

    $res1 = createReviewTestReservation($client1, $car, ReservationStatus::COMPLETED);
    $res2 = createReviewTestReservation($client2, $car, ReservationStatus::COMPLETED);
    $res3 = createReviewTestReservation($client3, $car, ReservationStatus::COMPLETED);

    CarReview::create([
        'car_id' => $car->id,
        'user_id' => $client1->id,
        'reservation_id' => $res1->id,
        'rating' => 5,
        'title' => 'Top notch',
        'comment' => 'Everything was flawless.',
        'is_published' => true,
    ]);

    CarReview::create([
        'car_id' => $car->id,
        'user_id' => $client2->id,
        'reservation_id' => $res2->id,
        'rating' => 4,
        'title' => 'Very good',
        'comment' => 'Comfortable and smooth drive.',
        'is_published' => true,
    ]);

    CarReview::create([
        'car_id' => $car->id,
        'user_id' => $client3->id,
        'reservation_id' => $res3->id,
        'rating' => 3,
        'title' => 'Average experience',
        'comment' => 'Small delay at pickup desk.',
        'is_published' => true,
    ]);

    $freshCar = $car->fresh();

    expect($freshCar->reviews_count)->toBe(3);
    // (5 + 4 + 3) / 3 = 4.0
    expect((float) $freshCar->average_rating)->toBe(4.0);

    $distribution = $freshCar->rating_distribution;
    expect($distribution[5]['count'])->toBe(1);
    expect($distribution[4]['count'])->toBe(1);
    expect($distribution[3]['count'])->toBe(1);
    expect($distribution[2]['count'])->toBe(0);
    expect($distribution[1]['count'])->toBe(0);
});

it('filters fleet by min_rating on /fleet', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);

    $highRatedCar = createReviewTestCar(['make' => 'HighRatedCar', 'model' => 'Speed']);
    $lowRatedCar = createReviewTestCar(['make' => 'LowRatedCar', 'model' => 'Slow']);

    $resHigh = createReviewTestReservation($client, $highRatedCar, ReservationStatus::COMPLETED);
    $resLow = createReviewTestReservation($client, $lowRatedCar, ReservationStatus::COMPLETED);

    CarReview::create([
        'car_id' => $highRatedCar->id,
        'user_id' => $client->id,
        'reservation_id' => $resHigh->id,
        'rating' => 5,
        'comment' => 'High rating car review.',
        'is_published' => true,
    ]);

    CarReview::create([
        'car_id' => $lowRatedCar->id,
        'user_id' => $client->id,
        'reservation_id' => $resLow->id,
        'rating' => 3,
        'comment' => 'Low rating car review.',
        'is_published' => true,
    ]);

    $response = $this->get(route('fleet', ['min_rating' => '4.0']));
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Fleet')
        ->has('cars.data', 1)
        ->where('cars.data.0.id', $highRatedCar->id)
    );
});

it('delivers reviews and eligibleReservation to booking show page', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();

    $completedRes = createReviewTestReservation($client, $car, ReservationStatus::COMPLETED);

    // Visiting vehicle booking page as client who completed a trip on this car
    $response = $this->actingAs($client)->get(route('fleet.show', $car));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Booking')
        ->has('reviews')
        ->has('ratingDistribution')
        ->where('eligibleReservation.id', $completedRes->id)
    );
});

it('allows admin to list and delete reviews', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();
    $res = createReviewTestReservation($client, $car, ReservationStatus::COMPLETED);

    $review = CarReview::create([
        'car_id' => $car->id,
        'user_id' => $client->id,
        'reservation_id' => $res->id,
        'rating' => 5,
        'title' => 'Inappropriate content to delete',
        'comment' => 'Some review comment.',
        'is_published' => true,
    ]);

    // Admin can list reviews
    $listResponse = $this->actingAs($admin)->get(route('admin.reviews.index'));
    $listResponse->assertOk();
    $listResponse->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Reviews/Index')
        ->has('reviews.data', 1)
        ->where('reviews.data.0.id', $review->id)
    );

    // Admin can delete review
    $deleteResponse = $this->actingAs($admin)->delete(route('admin.reviews.destroy', $review));
    $deleteResponse->assertRedirect();
    $this->assertDatabaseMissing('car_reviews', ['id' => $review->id]);
});

it('prevents non-admin client from accessing admin reviews routes', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createReviewTestCar();
    $res = createReviewTestReservation($client, $car, ReservationStatus::COMPLETED);

    $review = CarReview::create([
        'car_id' => $car->id,
        'user_id' => $client->id,
        'reservation_id' => $res->id,
        'rating' => 5,
        'comment' => 'Client trying to delete this.',
        'is_published' => true,
    ]);

    // Attempt to access admin reviews list
    $this->actingAs($client)->get(route('admin.reviews.index'))->assertForbidden();

    // Attempt to delete review
    $this->actingAs($client)->delete(route('admin.reviews.destroy', $review))->assertForbidden();

    $this->assertDatabaseHas('car_reviews', ['id' => $review->id]);
});
