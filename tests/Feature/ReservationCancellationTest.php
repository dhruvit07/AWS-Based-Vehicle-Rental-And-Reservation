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
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createCancelTestCar(array $attributes = []): Car
{
    return Car::create(array_merge([
        'make' => 'Audi',
        'model' => 'A6',
        'year' => 2024,
        'license_plate' => 'CAN-' . fake()->unique()->numerify('####'),
        'color' => CarColor::BLACK,
        'price_per_day' => 120.00,
        'mileage' => 12000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ], $attributes));
}

function createCancelTestReservation(
    User $user,
    Car $car,
    ReservationStatus $status = ReservationStatus::CONFIRMED,
    ?string $startDate = null,
    ?string $endDate = null
): Reservation {
    $start = $startDate ?? Carbon::now()->addDays(5)->format('Y-m-d');
    $end = $endDate ?? Carbon::now()->addDays(8)->format('Y-m-d');
    $days = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
    $dailyRate = $car->price_per_day;
    $subtotal = $dailyRate * $days;
    $tax = $subtotal * 0.07;
    $total = $subtotal + $tax;

    return Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => $start,
        'end_date' => $end,
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'Main Office',
        'return_location' => 'Main Office',
        'total_days' => $days,
        'daily_rate' => $dailyRate,
        'subtotal' => $subtotal,
        'tax_amount' => $tax,
        'discount_amount' => 0,
        'total_amount' => $total,
        'status' => $status,
    ]);
}

it('allows client to cancel an upcoming confirmed reservation with a valid preset reason and optional notes', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $reservation = createCancelTestReservation($client, $car, ReservationStatus::CONFIRMED);

    $payment = Payment::create([
        'reservation_id' => $reservation->id,
        'user_id' => $client->id,
        'amount' => $reservation->total_amount,
        'currency' => 'INR',
        'payment_method' => PaymentMethod::CREDIT_CARD,
        'status' => PaymentStatus::COMPLETED,
        'transaction_id' => 'TXN-INITIAL123',
        'processed_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($client)
        ->from(route('client.reservations.show', $reservation->id))
        ->post(route('client.reservations.cancel', $reservation), [
            'reason' => 'Change of travel plans',
            'notes' => 'Flight got rescheduled by airline',
        ]);

    $response->assertRedirect(route('client.reservations.show', $reservation->id));
    $response->assertSessionHas('success');

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::CANCELLED)
        ->and($reservation->cancelled_at)->not->toBeNull()
        ->and($reservation->cancellation_reason)->toBe('Change of travel plans: Flight got rescheduled by airline');
});

it('transitions linked completed payment to REFUNDED with exact refunded_amount and refund transaction ID', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $reservation = createCancelTestReservation($client, $car, ReservationStatus::CONFIRMED);

    $payment = Payment::create([
        'reservation_id' => $reservation->id,
        'user_id' => $client->id,
        'amount' => $reservation->total_amount,
        'currency' => 'INR',
        'payment_method' => PaymentMethod::CREDIT_CARD,
        'status' => PaymentStatus::COMPLETED,
        'transaction_id' => 'TXN-PAY-999',
        'processed_at' => now()->subDay(),
    ]);

    $this->actingAs($client)
        ->post(route('client.reservations.cancel', $reservation), [
            'reason' => 'Trip postponed',
            'notes' => 'Postponed to next month',
        ]);

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::REFUNDED)
        ->and((float) $payment->refunded_amount)->toBe((float) $reservation->total_amount)
        ->and($payment->transaction_id)->toContain('REF-')
        ->and($payment->notes)->toContain('Full refund of ₹' . number_format($payment->amount) . ' processed automatically upon cancellation.');
});

it('transitions linked pending payment to CANCELLED upon reservation cancellation', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $reservation = createCancelTestReservation($client, $car, ReservationStatus::PENDING);

    $payment = Payment::create([
        'reservation_id' => $reservation->id,
        'user_id' => $client->id,
        'amount' => $reservation->total_amount,
        'currency' => 'INR',
        'payment_method' => PaymentMethod::CASH,
        'status' => PaymentStatus::PENDING,
    ]);

    $this->actingAs($client)
        ->post(route('client.reservations.cancel', $reservation), [
            'reason' => 'Found alternative transportation',
        ]);

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::CANCELLED);
});

it('immediately frees vehicle dates for re-booking after cancellation', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $start = Carbon::now()->addDays(10)->format('Y-m-d');
    $end = Carbon::now()->addDays(14)->format('Y-m-d');

    $reservation = createCancelTestReservation($client, $car, ReservationStatus::CONFIRMED, $start, $end);

    // Car should NOT be available during these dates
    expect($car->isAvailable($start, $end))->toBeFalse();

    // Client cancels the reservation
    $this->actingAs($client)
        ->post(route('client.reservations.cancel', $reservation), [
            'reason' => 'Personal or medical emergency',
        ]);

    // Car must now immediately be available for re-booking!
    expect($car->isAvailable($start, $end))->toBeTrue();
});

it('prevents a client from cancelling another user reservation', function () {
    $owner = User::factory()->create(['role' => UserRole::CLIENT]);
    $attacker = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $reservation = createCancelTestReservation($owner, $car, ReservationStatus::CONFIRMED);

    $response = $this->actingAs($attacker)
        ->post(route('client.reservations.cancel', $reservation), [
            'reason' => 'Change of travel plans',
        ]);

    $response->assertStatus(403);

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::CONFIRMED)
        ->and($reservation->cancelled_at)->toBeNull();
});

it('prevents cancelling past or completed reservations', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();

    // Completed past reservation
    $pastReservation = createCancelTestReservation(
        $client,
        $car,
        ReservationStatus::COMPLETED,
        Carbon::now()->subDays(10)->format('Y-m-d'),
        Carbon::now()->subDays(5)->format('Y-m-d')
    );

    $response = $this->actingAs($client)
        ->post(route('client.reservations.cancel', $pastReservation), [
            'reason' => 'Other',
            'notes' => 'Attempting past cancel',
        ]);

    $response->assertSessionHas('error');

    $pastReservation->refresh();
    expect($pastReservation->status)->toBe(ReservationStatus::COMPLETED)
        ->and($pastReservation->cancelled_at)->toBeNull();
});

it('prevents cancelling an already cancelled reservation', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $reservation = createCancelTestReservation($client, $car, ReservationStatus::CANCELLED);
    $reservation->update(['cancelled_at' => now()->subDay()]);

    $response = $this->actingAs($client)
        ->post(route('client.reservations.cancel', $reservation), [
            'reason' => 'Change of travel plans',
        ]);

    $response->assertSessionHas('error');
});

it('validates preset reason when cancelling', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $reservation = createCancelTestReservation($client, $car, ReservationStatus::CONFIRMED);

    $response = $this->actingAs($client)
        ->post(route('client.reservations.cancel', $reservation), [
            'reason' => 'Non-existent random reason',
        ]);

    $response->assertSessionHasErrors(['reason']);

    $reservation->refresh();
    expect($reservation->status)->toBe(ReservationStatus::CONFIRMED);
});

it('displays cancellation details and refund record on admin reservation show page', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createCancelTestCar();
    $reservation = createCancelTestReservation($client, $car, ReservationStatus::CANCELLED);
    $reservation->update([
        'cancellation_reason' => 'Inclement weather / flight disruption: Severe storm warning',
        'cancelled_at' => now(),
    ]);

    Payment::create([
        'reservation_id' => $reservation->id,
        'user_id' => $client->id,
        'amount' => $reservation->total_amount,
        'currency' => 'INR',
        'payment_method' => PaymentMethod::CREDIT_CARD,
        'status' => PaymentStatus::REFUNDED,
        'refunded_amount' => $reservation->total_amount,
        'transaction_id' => 'TXN-123 | REF-ABC999TEST',
        'notes' => 'Full refund processed automatically upon cancellation.',
        'processed_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reservations.show', $reservation->id));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Reservations/Show')
        ->has('reservation', fn (Assert $res) => $res
            ->where('id', $reservation->id)
            ->where('status', 'cancelled')
            ->where('cancellation_reason', 'Inclement weather / flight disruption: Severe storm warning')
            ->has('payments', 1)
            ->etc()
        )
    );
});
