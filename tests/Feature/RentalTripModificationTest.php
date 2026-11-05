<?php

use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createModificationTestCar(array $attributes = []): Car
{
    return Car::create(array_merge([
        'make' => 'Porsche',
        'model' => 'Taycan',
        'year' => 2024,
        'license_plate' => 'MOD-' . fake()->unique()->numerify('####'),
        'color' => CarColor::WHITE,
        'price_per_day' => 8000.00,
        'mileage' => 8500,
        'transmission' => 'automatic',
        'seats' => 4,
        'fuel_type' => FuelType::ELECTRIC,
        'status' => CarStatus::AVAILABLE,
    ], $attributes));
}

function createModificationTestReservation(
    User $user,
    Car $car,
    ReservationStatus $status = ReservationStatus::CONFIRMED,
    ?string $startDate = null,
    ?string $endDate = null,
    int $days = 3
): Reservation {
    $start = $startDate ?? Carbon::now()->addDays(2)->format('Y-m-d');
    $end = $endDate ?? Carbon::now()->addDays(2 + $days - 1)->format('Y-m-d');
    $dailyRate = (float) $car->price_per_day;
    $subtotal = $dailyRate * $days;
    $tax = round($subtotal * 0.18, 2);
    $total = $subtotal + $tax;

    $extras = [
        [
            'id' => 'insurance',
            'name' => 'Zero-Deductible Insurance',
            'rate' => 499.00,
            'type' => 'per_day',
            'days' => $days,
            'total' => 499.00 * $days,
        ],
    ];

    $reservation = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => $start,
        'end_date' => $end,
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'total_days' => $days,
        'daily_rate' => $dailyRate,
        'subtotal' => $subtotal + (499.00 * $days),
        'tax_amount' => $tax,
        'discount_amount' => 0,
        'total_amount' => $total + (499.00 * $days * 1.18),
        'extras' => $extras,
        'status' => $status,
    ]);

    Payment::create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'amount' => $reservation->total_amount,
        'currency' => 'INR',
        'payment_method' => PaymentMethod::CREDIT_CARD,
        'status' => PaymentStatus::COMPLETED,
        'transaction_id' => 'TXN-MOD-' . fake()->unique()->numerify('######'),
        'processed_at' => now(),
    ]);

    return $reservation;
}

test('client can check extension availability and receive accurate differential pricing', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createModificationTestCar();
    $reservation = createModificationTestReservation($client, $car, ReservationStatus::CONFIRMED);

    $response = $this->actingAs($client)->postJson(route('client.reservations.extend.check', $reservation), [
        'additional_days' => 2,
    ]);

    $response->assertOk();
    $response->assertJson([
        'available' => true,
        'extra_days' => 2,
        'daily_rate' => 8000.0,
        'daily_extras_rate' => 499.0,
        'differential_base' => 16000.0,
        'differential_extras' => 998.0,
        'differential_subtotal' => 16998.0,
        'differential_tax' => 3059.64,
        'differential_total' => 20057.64,
    ]);
});

test('extension check identifies collision with future booking on the same car', function () {
    $client1 = User::factory()->create(['role' => UserRole::CLIENT]);
    $client2 = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createModificationTestCar();

    // Client 1 has reservation for Nov 10 - Nov 12
    $start1 = Carbon::now()->addDays(5)->format('Y-m-d');
    $end1 = Carbon::now()->addDays(7)->format('Y-m-d');
    $reservation1 = createModificationTestReservation($client1, $car, ReservationStatus::CONFIRMED, $start1, $end1, 3);

    // Client 2 already booked the same car for Nov 13 - Nov 15
    $start2 = Carbon::now()->addDays(8)->format('Y-m-d');
    $end2 = Carbon::now()->addDays(10)->format('Y-m-d');
    createModificationTestReservation($client2, $car, ReservationStatus::CONFIRMED, $start2, $end2, 3);

    // Client 1 attempts to extend by 2 days (which touches Nov 13 and Nov 14 -> conflict!)
    $response = $this->actingAs($client1)->postJson(route('client.reservations.extend.check', $reservation1), [
        'additional_days' => 2,
    ]);

    $response->assertOk();
    $response->assertJson([
        'available' => false,
    ]);
});

test('client can successfully extend their trip and create differential payment', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createModificationTestCar();
    $reservation = createModificationTestReservation($client, $car, ReservationStatus::CONFIRMED);

    $oldEndDate = $reservation->end_date;
    $oldTotal = (float) $reservation->total_amount;

    $response = $this->actingAs($client)->post(route('client.reservations.extend', $reservation), [
        'additional_days' => 2,
        'payment_method' => 'upi',
    ]);

    $response->assertSessionHas('success');

    $reservation->refresh();
    expect($reservation->total_days)->toBe(5);
    expect(Carbon::parse($reservation->end_date)->isAfter(Carbon::parse($oldEndDate)))->toBeTrue();
    expect((float) $reservation->total_amount)->toBeGreaterThan($oldTotal);

    // A secondary payment record was created
    expect($reservation->payments()->count())->toBe(2);
    $secondPayment = $reservation->payments()->latest('id')->first();
    expect($secondPayment->status)->toBe(PaymentStatus::COMPLETED);
    expect($secondPayment->payment_method)->toBe(PaymentMethod::UPI);
});

test('client cannot extend another users reservation', function () {
    $owner = User::factory()->create(['role' => UserRole::CLIENT]);
    $other = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createModificationTestCar();
    $reservation = createModificationTestReservation($owner, $car);

    $response = $this->actingAs($other)->post(route('client.reservations.extend', $reservation), [
        'additional_days' => 1,
    ]);

    $response->assertForbidden();
});

test('cannot extend cancelled reservation', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createModificationTestCar();
    $reservation = createModificationTestReservation($client, $car, ReservationStatus::CANCELLED);

    $response = $this->actingAs($client)->post(route('client.reservations.extend', $reservation), [
        'additional_days' => 1,
    ]);

    $response->assertSessionHas('error');
});

test('client can execute early return on active trip and receive prorated refund', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createModificationTestCar(['status' => CarStatus::RENTED]);

    // Active reservation started 1 day ago and was scheduled for 5 days
    $start = Carbon::now()->subDay()->format('Y-m-d');
    $end = Carbon::now()->addDays(3)->format('Y-m-d');
    $reservation = createModificationTestReservation($client, $car, ReservationStatus::ACTIVE, $start, $end, 5);

    $response = $this->actingAs($client)->post(route('client.reservations.earlyReturn', $reservation));

    $response->assertSessionHas('success');

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::COMPLETED);
    expect($car->fresh()->status)->toBe(CarStatus::CLEANING);

    // Refund payment record exists
    $refundPayment = $reservation->payments()->where('status', PaymentStatus::REFUNDED)->first();
    expect($refundPayment)->not->toBeNull();
    expect((float) $refundPayment->refunded_amount)->toBeGreaterThan(0);
});

test('cannot early return non-active reservation', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createModificationTestCar();
    $reservation = createModificationTestReservation($client, $car, ReservationStatus::CONFIRMED);

    $response = $this->actingAs($client)->post(route('client.reservations.earlyReturn', $reservation));

    $response->assertSessionHas('error');
    expect($reservation->fresh()->status)->toBe(ReservationStatus::CONFIRMED);
});
