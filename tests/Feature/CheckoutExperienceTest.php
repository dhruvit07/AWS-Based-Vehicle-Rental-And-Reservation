<?php

use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\CouponsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(CouponsSeeder::class);
});

function createTestCar(array $attributes = []): Car
{
    return Car::create(array_merge([
        'make' => 'BMW',
        'model' => '3 Series',
        'year' => 2024,
        'license_plate' => 'IND-' . fake()->unique()->numerify('####'),
        'color' => CarColor::BLACK,
        'price_per_day' => 1500.00,
        'mileage' => 12000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ], $attributes));
}

test('coupon validation endpoint validates COLLEGE20 correctly', function () {
    // COLLEGE20: 20%, min_spend 1000, max_discount 2000
    // Subtotal 5000: 20% is 1000, within max_discount 2000
    $response = $this->postJson(route('coupons.validate'), [
        'code' => 'college20',
        'subtotal' => 5000,
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'code' => 'COLLEGE20',
            'type' => 'percentage',
            'value' => 20,
            'discount_amount' => 1000,
        ]);
});

test('coupon validation endpoint caps percentage discount at max_discount', function () {
    // COLLEGE20: max_discount 2000
    // Subtotal 15000: 20% is 3000, but capped at 2000
    $response = $this->postJson(route('coupons.validate'), [
        'code' => 'COLLEGE20',
        'subtotal' => 15000,
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'code' => 'COLLEGE20',
            'discount_amount' => 2000,
        ]);
});

test('coupon validation fails when subtotal is below min_spend', function () {
    // FLAT500: min_spend 2000
    $response = $this->postJson(route('coupons.validate'), [
        'code' => 'FLAT500',
        'subtotal' => 1500,
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => false,
        ]);
    expect($response->json('message'))->toContain('Minimum order spend');
});

test('coupon validation validates FLAT500 when subtotal meets min_spend', function () {
    // FLAT500: fixed 500, min_spend 2000
    $response = $this->postJson(route('coupons.validate'), [
        'code' => 'FLAT500',
        'subtotal' => 2500,
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'code' => 'FLAT500',
            'type' => 'fixed',
            'value' => 500,
            'discount_amount' => 500,
        ]);
});

test('coupon validation fails on invalid, expired, or inactive coupon', function () {
    // Invalid code
    $invalid = $this->postJson(route('coupons.validate'), [
        'code' => 'NONEXISTENT',
        'subtotal' => 2500,
    ]);
    $invalid->assertOk()->assertJson(['valid' => false]);

    // Expired coupon
    $expired = Coupon::create([
        'code' => 'EXPIRED99',
        'type' => 'fixed',
        'value' => 100,
        'min_spend' => 0,
        'is_active' => true,
        'expires_at' => now()->subDay(),
    ]);
    $expiredResp = $this->postJson(route('coupons.validate'), [
        'code' => 'EXPIRED99',
        'subtotal' => 1000,
    ]);
    $expiredResp->assertOk()->assertJson(['valid' => false]);
    expect($expiredResp->json('message'))->toContain('expired');

    // Inactive coupon
    Coupon::create([
        'code' => 'INACTIVE99',
        'type' => 'fixed',
        'value' => 100,
        'min_spend' => 0,
        'is_active' => false,
    ]);
    $inactiveResp = $this->postJson(route('coupons.validate'), [
        'code' => 'INACTIVE99',
        'subtotal' => 1000,
    ]);
    $inactiveResp->assertOk()->assertJson(['valid' => false]);
});

test('it creates a booking with extras, coupon discount, and payment record', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createTestCar(['price_per_day' => 2000.00]);

    // Booking for 3 days: 2026-12-01 to 2026-12-04 = 3 days
    // Car base: 2000 * 3 = 6000
    // Extras:
    // insurance: 499 * 3 = 1497
    // additional_driver: 499 (fixed)
    // Extras total: 1497 + 499 = 1996
    // Subtotal: 6000 + 1996 = 7996
    // Coupon COLLEGE20: 20% of 7996 = 1599.20 (max 2000, so 1599.20)
    // Taxable: 7996 - 1599.20 = 6396.80
    // Tax 7%: 6396.80 * 0.07 = 447.78
    // Total: 6396.80 + 447.78 = 6844.58

    $response = $this->actingAs($user)->post(route('fleet.book', $car), [
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-04',
        'pickup_location' => 'Airport Terminal 1',
        'return_location' => 'Downtown Office',
        'extras' => ['insurance', 'additional_driver'],
        'coupon_code' => 'college20',
        'payment_method' => 'credit_card',
        'transaction_id' => 'TXN-TEST-12345',
    ]);

    $reservation = Reservation::first();
    expect($reservation)->not->toBeNull();
    $response->assertRedirect(route('booking.confirmation', $reservation));

    expect($reservation->status)->toBe(ReservationStatus::CONFIRMED);
    expect($reservation->total_days)->toBe(3);
    expect((float) $reservation->daily_rate)->toBe(2000.00);
    expect((float) $reservation->subtotal)->toBe(7996.00);
    expect((float) $reservation->discount_amount)->toBe(1599.20);
    expect($reservation->coupon_code)->toBe('COLLEGE20');
    expect((float) $reservation->tax_amount)->toBe(447.78);
    expect((float) $reservation->total_amount)->toBe(6844.58);

    // Verify extras json structure
    expect($reservation->extras)->toBeArray();
    expect($reservation->extras)->toHaveCount(2);
    expect($reservation->extras[0]['id'])->toBe('insurance');
    expect($reservation->extras[0]['days'])->toBe(3);
    expect((float) $reservation->extras[0]['total'])->toBe(1497.0);
    expect($reservation->extras[1]['id'])->toBe('additional_driver');
    expect($reservation->extras[1]['type'])->toBe('fixed');
    expect((float) $reservation->extras[1]['total'])->toBe(499.0);

    // Verify payment record
    $payment = Payment::where('reservation_id', $reservation->id)->first();
    expect($payment)->not->toBeNull();
    expect((float) $payment->amount)->toBe(6844.58);
    expect($payment->payment_method)->toBe(PaymentMethod::CREDIT_CARD);
    expect($payment->status)->toBe(PaymentStatus::COMPLETED);
    expect($payment->transaction_id)->toBe('TXN-TEST-12345');
    expect($payment->user_id)->toBe($user->id);
});

test('it creates a booking with upi and generates transaction_id if not provided', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createTestCar(['price_per_day' => 1000.00]);

    $response = $this->actingAs($user)->post(route('fleet.book', $car), [
        'start_date' => '2026-12-10',
        'end_date' => '2026-12-11',
        'pickup_location' => 'Central Station',
        'return_location' => 'Central Station',
        'extras' => ['gps'],
        'payment_method' => 'upi',
    ]);

    $reservation = Reservation::first();
    expect($reservation)->not->toBeNull();
    $response->assertRedirect(route('booking.confirmation', $reservation));

    $payment = $reservation->payments()->first();
    expect($payment)->not->toBeNull();
    expect($payment->payment_method)->toBe(PaymentMethod::UPI);
    expect($payment->transaction_id)->toStartWith('TXN-');
    expect($payment->status)->toBe(PaymentStatus::COMPLETED);
});

test('confirmation page renders reservation with loaded car, user, and payments', function () {
    $user = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createTestCar(['price_per_day' => 1000.00]);

    $this->actingAs($user)->post(route('fleet.book', $car), [
        'start_date' => '2026-12-10',
        'end_date' => '2026-12-12',
        'pickup_location' => 'Airport Terminal 1',
        'return_location' => 'Airport Terminal 1',
        'extras' => ['gps'],
        'coupon_code' => 'FIRSTDRIVE',
        'payment_method' => 'credit_card',
    ]);

    $reservation = Reservation::first();

    $response = $this->actingAs($user)->get(route('booking.confirmation', $reservation));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('BookingConfirmation')
        ->has('reservation')
        ->where('reservation.id', $reservation->id)
        ->has('reservation.payments')
        ->where('reservation.coupon_code', 'FIRSTDRIVE')
    );

    // Another user cannot view this confirmation
    $anotherUser = User::factory()->create(['role' => UserRole::CLIENT]);
    $unauthResponse = $this->actingAs($anotherUser)->get(route('booking.confirmation', $reservation));
    $unauthResponse->assertRedirect(route('fleet'));
});
