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

function createAgreementTestCar(array $attributes = []): Car
{
    return Car::create(array_merge([
        'make' => 'BMW',
        'model' => '3 Series',
        'year' => 2024,
        'license_plate' => 'AGR-' . fake()->unique()->numerify('####'),
        'color' => CarColor::BLUE,
        'price_per_day' => 4500.00,
        'mileage' => 14500,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ], $attributes));
}

function createAgreementTestReservation(
    User $user,
    Car $car,
    ReservationStatus $status = ReservationStatus::CONFIRMED
): Reservation {
    $start = Carbon::now()->addDays(2)->format('Y-m-d');
    $end = Carbon::now()->addDays(5)->format('Y-m-d');
    $days = 3;
    $dailyRate = $car->price_per_day;
    $subtotal = $dailyRate * $days;
    $tax = $subtotal * 0.18;
    $total = $subtotal + $tax;

    $reservation = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $user->id,
        'start_date' => $start,
        'end_date' => $end,
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'IGI Airport Terminal 3',
        'return_location' => 'IGI Airport Terminal 3',
        'total_days' => $days,
        'daily_rate' => $dailyRate,
        'subtotal' => $subtotal,
        'tax_amount' => $tax,
        'discount_amount' => 0,
        'total_amount' => $total,
        'status' => $status,
    ]);

    Payment::create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'amount' => $total,
        'currency' => 'INR',
        'payment_method' => PaymentMethod::CREDIT_CARD,
        'status' => PaymentStatus::COMPLETED,
        'transaction_id' => 'TXN-AGR-10293',
        'processed_at' => now(),
    ]);

    return $reservation;
}

test('guest is redirected to login when trying to view rental agreement', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createAgreementTestCar();
    $reservation = createAgreementTestReservation($user, $car);

    $response = $this->get(route('booking.agreement', $reservation));

    $response->assertRedirect(route('login'));
});

test('authenticated client can view their own rental agreement', function () {
    $user = User::factory()->create([
        'role' => UserRole::CLIENT,
        'name' => 'Aditya Sharma',
        'license_number' => 'DL-0420199081',
    ]);
    $car = createAgreementTestCar();
    $reservation = createAgreementTestReservation($user, $car);

    $response = $this->actingAs($user)->get(route('booking.agreement', $reservation));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('RentalAgreement')
        ->has('reservation')
        ->where('reservation.reservation_number', $reservation->reservation_number)
        ->has('verificationUrl')
        ->has('securityHash')
        ->has('pdiChecklist')
        ->where('pdiChecklist.fuel_level', '100% (Full to Full)')
        ->where('pdiChecklist.zones.front.status', 'Clear')
    );
});

test('client cannot view another users rental agreement', function () {
    $owner = User::factory()->create(['role' => UserRole::CLIENT]);
    $otherClient = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createAgreementTestCar();
    $reservation = createAgreementTestReservation($owner, $car);

    $response = $this->actingAs($otherClient)->get(route('booking.agreement', $reservation));

    $response->assertForbidden();
});

test('admin can view any clients rental agreement', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $car = createAgreementTestCar();
    $reservation = createAgreementTestReservation($client, $car);

    $response = $this->actingAs($admin)->get(route('booking.agreement', $reservation));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('RentalAgreement')
        ->where('reservation.reservation_number', $reservation->reservation_number)
    );
});

test('public voucher verification returns 200 with authorized badge for confirmed reservation', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT, 'name' => 'Meera Patel']);
    $car = createAgreementTestCar(['license_plate' => 'DL-01-XX-9999']);
    $reservation = createAgreementTestReservation($client, $car, ReservationStatus::CONFIRMED);

    // Unauthenticated request to simulate smartphone QR scan
    $response = $this->get(route('voucher.verify', ['reservation_number' => $reservation->reservation_number]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('VoucherVerification')
        ->where('found', true)
        ->where('isAuthorized', true)
        ->where('reservationNumber', $reservation->reservation_number)
        ->where('reservation.car.license_plate', 'DL-01-XX-9999')
        ->where('reservation.user.name', 'Meera Patel')
    );
});

test('public voucher verification indicates not authorized when reservation is cancelled', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createAgreementTestCar();
    $reservation = createAgreementTestReservation($client, $car, ReservationStatus::CANCELLED);

    $response = $this->get(route('voucher.verify', ['reservation_number' => $reservation->reservation_number]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('VoucherVerification')
        ->where('found', true)
        ->where('isAuthorized', false)
    );
});

test('public voucher verification returns found false for invalid reservation number', function () {
    $response = $this->get(route('voucher.verify', ['reservation_number' => 'NON-EXISTENT-VOUCHER']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('VoucherVerification')
        ->where('found', false)
        ->where('isAuthorized', false)
        ->where('reservationNumber', 'NON-EXISTENT-VOUCHER')
    );
});
