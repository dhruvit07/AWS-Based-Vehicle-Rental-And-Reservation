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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Fleet Admin',
        'email' => 'admin@example.com',
        'role' => UserRole::ADMIN,
    ]);

    $this->client = User::factory()->create([
        'name' => 'Loyal Customer',
        'email' => 'client@example.com',
        'role' => UserRole::CLIENT,
        'loyalty_tier' => 'bronze',
    ]);

    $this->car = Car::create([
        'make' => 'Audi',
        'model' => 'RS5',
        'year' => 2024,
        'license_plate' => 'DL-LOYAL-01',
        'color' => CarColor::BLACK,
        'price_per_day' => 10000.00,
        'mileage' => 5000,
        'transmission' => 'automatic',
        'seats' => 4,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ]);
});

it('calculates correct loyalty discount rates for each tier', function () {
    $this->client->loyalty_tier = 'bronze';
    expect($this->client->loyalty_discount_rate)->toBe(0.00);

    $this->client->loyalty_tier = 'silver';
    expect($this->client->loyalty_discount_rate)->toBe(0.05);

    $this->client->loyalty_tier = 'gold';
    expect($this->client->loyalty_discount_rate)->toBe(0.10);

    $this->client->loyalty_tier = 'platinum';
    expect($this->client->loyalty_discount_rate)->toBe(0.15);
});

it('automatically recalculates and promotes loyalty tier based on completed trips count', function () {
    // 2 completed trips -> bronze
    for ($i = 0; $i < 2; $i++) {
        Reservation::create([
            'reservation_number' => "RES-LOYAL-00{$i}",
            'user_id' => $this->client->id,
            'car_id' => $this->car->id,
            'start_date' => Carbon::now()->subDays(10 - $i)->format('Y-m-d'),
            'end_date' => Carbon::now()->subDays(9 - $i)->format('Y-m-d'),
            'total_days' => 1,
            'daily_rate' => 5000.00,
            'subtotal' => 5000.00,
            'tax_amount' => 350.00,
            'total_amount' => 5350.00,
            'status' => ReservationStatus::COMPLETED,
        ]);
    }

    $this->client->recalculateLoyaltyStats();
    expect($this->client->loyalty_tier)->toBe('bronze');

    // 3rd completed trip -> promotes to silver
    Reservation::create([
        'reservation_number' => 'RES-LOYAL-003',
        'user_id' => $this->client->id,
        'car_id' => $this->car->id,
        'start_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
        'end_date' => Carbon::now()->subDays(4)->format('Y-m-d'),
        'total_days' => 1,
        'daily_rate' => 5000.00,
        'subtotal' => 5000.00,
        'tax_amount' => 350.00,
        'total_amount' => 5350.00,
        'status' => ReservationStatus::COMPLETED,
    ]);

    $this->client->recalculateLoyaltyStats();
    expect($this->client->loyalty_tier)->toBe('silver');
    expect($this->client->completed_trips_count)->toBe(3);
});

it('promotes loyalty tier based on lifetime spend threshold', function () {
    // 1 completed trip of ₹105,000 -> promotes straight to platinum
    Reservation::create([
        'reservation_number' => 'RES-EXPENSIVE-01',
        'user_id' => $this->client->id,
        'car_id' => $this->car->id,
        'start_date' => Carbon::now()->subDays(15)->format('Y-m-d'),
        'end_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
        'total_days' => 10,
        'daily_rate' => 10000.00,
        'subtotal' => 100000.00,
        'tax_amount' => 7000.00,
        'total_amount' => 107000.00,
        'status' => ReservationStatus::COMPLETED,
    ]);

    $this->client->recalculateLoyaltyStats();
    expect($this->client->loyalty_tier)->toBe('platinum');
});

it('renders the loyalty rewards hub page with status 200 and tier metadata', function () {
    $this->actingAs($this->client);

    $response = $this->get(route('client.loyalty.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Client/Loyalty/Index')
        ->has('tierMeta')
        ->has('stats')
        ->has('allTiers', 4)
        ->has('recentTrips')
        ->where('tierMeta.name', 'bronze')
        ->where('tierMeta.discount_percent', 0)
    );
});

it('allows client or evaluator to simulate any loyalty tier on demand', function () {
    $this->actingAs($this->client);

    $response = $this->post(route('client.loyalty.simulate'), [
        'tier' => 'platinum',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->client->refresh();
    expect($this->client->loyalty_tier)->toBe('platinum');
    expect($this->client->loyalty_discount_rate)->toBe(0.15);
});

it('applies automatic loyalty discount at vehicle booking checkout', function () {
    $this->client->loyalty_tier = 'gold'; // 10% discount
    $this->client->save();

    $this->actingAs($this->client);

    // 3 days @ 10,000/day = 30,000 subtotal
    // 10% loyalty discount = 3,000
    // Taxable = 27,000; Tax (7%) = 1,890; Total = 28,890
    $response = $this->post(route('fleet.book', $this->car->id), [
        'start_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
        'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'extras' => [],
        'payment_method' => 'credit_card',
        'transaction_id' => 'TXN-LOYALTY-001',
    ]);

    $response->assertRedirect();

    $reservation = Reservation::where('user_id', $this->client->id)->latest('id')->first();
    expect($reservation)->not->toBeNull();
    expect((float) $reservation->subtotal)->toBe(30000.00);
    expect((float) $reservation->loyalty_discount_amount)->toBe(3000.00);
    expect($reservation->applied_loyalty_tier)->toBe('gold');
    expect((float) $reservation->tax_amount)->toBe(1890.00);
    expect((float) $reservation->total_amount)->toBe(28890.00);
});

it('stacks loyalty discount with promo coupons seamlessly', function () {
    $this->client->loyalty_tier = 'silver'; // 5% discount
    $this->client->save();

    Coupon::create([
        'code' => 'LOYAL500',
        'type' => 'fixed',
        'value' => 500.00,
        'min_spend' => 1000.00,
        'is_active' => true,
    ]);

    $this->actingAs($this->client);

    // 2 days @ 10,000/day = 20,000 subtotal
    // 5% loyalty discount = 1,000
    // Coupon discount = 500
    // Taxable = 18,500; Tax (7%) = 1,295; Total = 19,795
    $response = $this->post(route('fleet.book', $this->car->id), [
        'start_date' => Carbon::now()->addDays(6)->format('Y-m-d'),
        'end_date' => Carbon::now()->addDays(8)->format('Y-m-d'),
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'coupon_code' => 'LOYAL500',
        'extras' => [],
        'payment_method' => 'credit_card',
        'transaction_id' => 'TXN-STACK-001',
    ]);

    $response->assertRedirect();

    $reservation = Reservation::where('user_id', $this->client->id)->latest('id')->first();
    expect($reservation)->not->toBeNull();
    expect((float) $reservation->subtotal)->toBe(20000.00);
    expect((float) $reservation->loyalty_discount_amount)->toBe(1000.00);
    expect((float) $reservation->discount_amount)->toBe(500.00);
    expect($reservation->coupon_code)->toBe('LOYAL500');
    expect((float) $reservation->tax_amount)->toBe(1295.00);
    expect((float) $reservation->total_amount)->toBe(19795.00);
});

it('surfaces VIP tier in admin operations handover queue', function () {
    $this->client->loyalty_tier = 'platinum';
    $this->client->save();

    Reservation::create([
        'reservation_number' => 'RES-VIP-PICKUP',
        'user_id' => $this->client->id,
        'car_id' => $this->car->id,
        'start_date' => Carbon::now()->addDay()->format('Y-m-d'),
        'end_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
        'total_days' => 2,
        'daily_rate' => 10000.00,
        'subtotal' => 20000.00,
        'tax_amount' => 1400.00,
        'total_amount' => 21400.00,
        'status' => ReservationStatus::CONFIRMED,
    ]);

    $this->actingAs($this->admin);

    $response = $this->get(route('admin.operations'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Operations/Index')
        ->has('pickupsDue', 1)
        ->where('pickupsDue.0.user.loyalty_tier', 'platinum')
    );
});
