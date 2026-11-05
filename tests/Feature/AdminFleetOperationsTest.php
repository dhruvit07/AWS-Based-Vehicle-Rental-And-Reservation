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

function createOperationsTestCar(array $attributes = []): Car
{
    return Car::create(array_merge([
        'make' => 'Mercedes-Benz',
        'model' => 'C-Class',
        'year' => 2024,
        'license_plate' => 'OPS-' . fake()->unique()->numerify('####'),
        'color' => CarColor::SILVER,
        'price_per_day' => 5000.00,
        'mileage' => 15000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ], $attributes));
}

test('guest is redirected to login from operations command center', function () {
    $response = $this->get(route('admin.operations'));

    $response->assertRedirect(route('login'));
});

test('non-admin client is forbidden from operations command center', function () {
    $client = User::factory()->create(['role' => UserRole::CLIENT]);

    $response = $this->actingAs($client)->get(route('admin.operations'));

    $response->assertForbidden();
});

test('admin can access operations command center with telemetry and kpis', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $car1 = createOperationsTestCar(['status' => CarStatus::AVAILABLE]);
    $car2 = createOperationsTestCar(['status' => CarStatus::RENTED]);

    $response = $this->actingAs($admin)->get(route('admin.operations'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Operations/Index')
        ->has('kpis')
        ->where('kpis.total_fleet', 2)
        ->where('kpis.on_road', 1)
        ->where('kpis.available', 1)
        ->has('hubs', 4)
        ->has('fleet', 2)
        ->has('pickupsDue')
        ->has('returnsDue')
        ->has('carStatusOptions')
    );
});

test('admin root url loads operations command center', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $response = $this->actingAs($admin)->get(route('admin.home'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Operations/Index')
    );
});

test('admin can dispatch confirmed reservation to active and set car to rented', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createOperationsTestCar(['status' => CarStatus::AVAILABLE]);

    $reservation = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $client->id,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'end_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'total_days' => 3,
        'daily_rate' => $car->price_per_day,
        'subtotal' => 15000,
        'tax_amount' => 2700,
        'total_amount' => 17700,
        'status' => ReservationStatus::CONFIRMED,
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.operations.dispatch', $reservation));

    $response->assertSessionHas('success');
    expect($reservation->fresh()->status)->toBe(ReservationStatus::ACTIVE);
    expect($car->fresh()->status)->toBe(CarStatus::RENTED);
});

test('cannot dispatch non-confirmed reservation', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createOperationsTestCar(['status' => CarStatus::AVAILABLE]);

    $reservation = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $client->id,
        'start_date' => Carbon::now()->format('Y-m-d'),
        'end_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'total_days' => 3,
        'daily_rate' => $car->price_per_day,
        'subtotal' => 15000,
        'tax_amount' => 2700,
        'total_amount' => 17700,
        'status' => ReservationStatus::CANCELLED,
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.operations.dispatch', $reservation));

    $response->assertSessionHas('error');
    expect($reservation->fresh()->status)->toBe(ReservationStatus::CANCELLED);
});

test('admin can check in active reservation and update odometer and car status', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createOperationsTestCar(['status' => CarStatus::RENTED, 'mileage' => 12000]);

    $reservation = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $client->id,
        'start_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
        'end_date' => Carbon::now()->format('Y-m-d'),
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'total_days' => 2,
        'daily_rate' => $car->price_per_day,
        'subtotal' => 10000,
        'tax_amount' => 1800,
        'total_amount' => 11800,
        'status' => ReservationStatus::ACTIVE,
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.operations.checkin', $reservation), [
        'return_odometer' => 12280,
        'fuel_level' => 'full',
        'next_status' => 'cleaning',
        'condition_notes' => 'Vehicle returned in clean shape.',
    ]);

    $response->assertSessionHas('success');
    expect($reservation->fresh()->status)->toBe(ReservationStatus::COMPLETED);
    expect($car->fresh()->mileage)->toBe(12280);
    expect($car->fresh()->status)->toBe(CarStatus::CLEANING);
});

test('return check in fails if return odometer is less than current vehicle mileage', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = createOperationsTestCar(['status' => CarStatus::RENTED, 'mileage' => 15000]);

    $reservation = Reservation::create([
        'car_id' => $car->id,
        'user_id' => $client->id,
        'start_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
        'end_date' => Carbon::now()->format('Y-m-d'),
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'total_days' => 2,
        'daily_rate' => $car->price_per_day,
        'subtotal' => 10000,
        'tax_amount' => 1800,
        'total_amount' => 11800,
        'status' => ReservationStatus::ACTIVE,
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.operations.checkin', $reservation), [
        'return_odometer' => 14000, // Invalid: less than 15000
        'fuel_level' => 'full',
        'next_status' => 'available',
    ]);

    $response->assertSessionHasErrors('return_odometer');
    expect($reservation->fresh()->status)->toBe(ReservationStatus::ACTIVE);
});

test('admin can quickly update car operational status', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $car = createOperationsTestCar(['status' => CarStatus::AVAILABLE]);

    $response = $this->actingAs($admin)->patch(route('admin.operations.carStatus', $car), [
        'status' => 'maintenance',
    ]);

    $response->assertSessionHas('success');
    expect($car->fresh()->status)->toBe(CarStatus::MAINTENANCE);
});
