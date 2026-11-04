<?php

use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createCar(array $attributes = []): Car
{
    return Car::create(array_merge([
        'make' => 'Toyota',
        'model' => 'Camry',
        'year' => 2024,
        'license_plate' => 'ABC-' . fake()->unique()->numerify('####'),
        'color' => CarColor::WHITE,
        'price_per_day' => 100.00,
        'mileage' => 15000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ], $attributes));
}

it('prevents overlapping booking on confirmed reservation', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCar();

    Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::CONFIRMED,
    ]);

    // Attempt to book overlapping dates: 2026-11-12 to 2026-11-14
    $anotherUser = User::factory()->create(['role' => UserRole::CLIENT]);
    $response = $this->actingAs($anotherUser)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-12',
        'end_date' => '2026-11-14',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
    ]);

    $response->assertSessionHasErrors('start_date');

    // Attempt to book overlapping end edge: 2026-11-15 to 2026-11-18
    $responseEdgeEnd = $this->actingAs($anotherUser)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-15',
        'end_date' => '2026-11-18',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
    ]);
    $responseEdgeEnd->assertSessionHasErrors('start_date');

    // Attempt to book overlapping start edge: 2026-11-08 to 2026-11-10
    $responseEdgeStart = $this->actingAs($anotherUser)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-08',
        'end_date' => '2026-11-10',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
    ]);
    $responseEdgeStart->assertSessionHasErrors('start_date');

    // Attempt to book encompassing range: 2026-11-05 to 2026-11-20
    $responseEncompassing = $this->actingAs($anotherUser)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-05',
        'end_date' => '2026-11-20',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
    ]);
    $responseEncompassing->assertSessionHasErrors('start_date');

    // Confirm that only 1 reservation exists in DB
    expect(Reservation::count())->toBe(1);
});

it('prevents overlapping booking on active reservation', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCar();

    Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::ACTIVE,
    ]);

    $anotherUser = User::factory()->create(['role' => UserRole::CLIENT]);
    $response = $this->actingAs($anotherUser)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-12',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
    ]);

    $response->assertSessionHasErrors('start_date');
    expect(Reservation::count())->toBe(1);
});

it('prevents overlapping booking on pending reservation within 30 minutes', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCar();

    $res = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::PENDING,
    ]);
    // Set created_at to 10 minutes ago
    $res->created_at = now()->subMinutes(10);
    $res->save();

    $anotherUser = User::factory()->create(['role' => UserRole::CLIENT]);
    $response = $this->actingAs($anotherUser)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-12',
        'end_date' => '2026-11-14',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
    ]);

    $response->assertSessionHasErrors('start_date');
    expect(Reservation::count())->toBe(1);
});

it('allows booking on pending reservation older than 30 minutes', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCar();

    $res = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::PENDING,
    ]);
    // Set created_at to 35 minutes ago
    $res->created_at = now()->subMinutes(35);
    $res->save();

    $anotherUser = User::factory()->create(['role' => UserRole::CLIENT]);
    $response = $this->actingAs($anotherUser)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Airport Terminal 1',
        'return_location' => 'Airport Terminal 1',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    expect(Reservation::count())->toBe(2);
});

it('allows non-overlapping booking for the same user on the same car', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCar();

    // Past booking for the same user
    Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-05',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 4,
        'daily_rate' => 100.00,
        'subtotal' => 400.00,
        'tax_amount' => 28.00,
        'discount_amount' => 0,
        'total_amount' => 428.00,
        'status' => ReservationStatus::CONFIRMED,
    ]);

    // Same user books another non-overlapping period
    $response = $this->actingAs($user)->post(route('fleet.book', $car), [
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    expect(Reservation::where('user_id', $user->id)->count())->toBe(2);
});

it('delivers bookedRanges to the Inertia booking view', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCar();

    Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::CONFIRMED,
    ]);

    $pendingRecent = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::PENDING,
    ]);
    $pendingRecent->created_at = now()->subMinutes(5);
    $pendingRecent->save();

    $pendingOld = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-05',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 4,
        'daily_rate' => 100.00,
        'subtotal' => 400.00,
        'tax_amount' => 28.00,
        'discount_amount' => 0,
        'total_amount' => 428.00,
        'status' => ReservationStatus::PENDING,
    ]);
    $pendingOld->created_at = now()->subMinutes(40);
    $pendingOld->save();

    $response = $this->get(route('fleet.show', $car));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Booking')
        ->has('bookedRanges', 2)
        ->where('bookedRanges.0.from', '2026-11-10')
        ->where('bookedRanges.0.to', '2026-11-15')
        ->where('bookedRanges.1.from', '2026-11-20')
        ->where('bookedRanges.1.to', '2026-11-25')
    );
});

it('expires stale pending reservations via artisan command', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCar();

    $stale = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-15',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::PENDING,
    ]);
    $stale->created_at = now()->subMinutes(35);
    $stale->save();

    $recent = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'pickup_location' => 'Downtown Office',
        'return_location' => 'Downtown Office',
        'total_days' => 5,
        'daily_rate' => 100.00,
        'subtotal' => 500.00,
        'tax_amount' => 35.00,
        'discount_amount' => 0,
        'total_amount' => 535.00,
        'status' => ReservationStatus::PENDING,
    ]);
    $recent->created_at = now()->subMinutes(10);
    $recent->save();

    $this->artisan('reservations:expire-pending')
        ->expectsOutput('Expired 1 pending reservation(s).')
        ->assertExitCode(0);

    $stale->refresh();
    $recent->refresh();

    expect($stale->status)->toBe(ReservationStatus::CANCELLED);
    expect($stale->cancellation_reason)->toBe('Payment/checkout session expired');
    expect($stale->cancelled_at)->not->toBeNull();

    expect($recent->status)->toBe(ReservationStatus::PENDING);
    expect($recent->cancellation_reason)->toBeNull();
});
