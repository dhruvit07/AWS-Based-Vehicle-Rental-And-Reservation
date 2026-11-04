<?php

use App\Enums\CarColor;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\UserRole;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('loads profile settings page with status 200', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
});

it('allows password to be updated with current password confirmation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('password.edit'))
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password-123',
            'password_confirmation' => 'new-secret-password-123',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('password.edit'));

    expect(Hash::check('new-secret-password-123', $user->refresh()->password))->toBeTrue();
});

it('loads appearance settings page with status 200', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertOk();
});

it('reflects dark appearance cookie in html response', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withUnencryptedCookie('appearance', 'dark')
        ->get(route('appearance.edit'));

    $response->assertOk();
    $response->assertSee('class="dark"', false);
});

it('loads two-factor settings page with status 200', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'));

    $response->assertOk();
});

it('allows admin to create a vehicle', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $carData = [
        'make' => 'BMW',
        'model' => 'M4 Competition',
        'year' => 2024,
        'license_plate' => 'MH-01-AB-1234',
        'color' => CarColor::BLACK->value,
        'price_per_day' => 250.00,
        'mileage' => 5000,
        'transmission' => 'automatic',
        'seats' => 4,
        'fuel_type' => FuelType::GASOLINE->value,
        'description' => 'High performance luxury coupe.',
        'status' => CarStatus::AVAILABLE->value,
    ];

    $response = $this->actingAs($admin)->post(route('admin.cars.store'), $carData);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.cars.index'));
    $response->assertSessionHas('success', 'Car created successfully.');

    $this->assertDatabaseHas('cars', [
        'license_plate' => 'MH-01-AB-1234',
        'make' => 'BMW',
        'model' => 'M4 Competition',
    ]);
});

it('allows admin to update a vehicle', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $car = Car::create([
        'make' => 'Audi',
        'model' => 'RS5',
        'year' => 2023,
        'license_plate' => 'DL-04-XY-9999',
        'color' => CarColor::GRAY->value,
        'price_per_day' => 220.00,
        'mileage' => 8000,
        'transmission' => 'automatic',
        'seats' => 4,
        'fuel_type' => FuelType::GASOLINE->value,
        'status' => CarStatus::AVAILABLE->value,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.cars.update', $car), [
        'make' => 'Audi',
        'model' => 'RS5 Sportback',
        'year' => 2023,
        'license_plate' => 'DL-04-XY-9999',
        'color' => CarColor::BLACK->value,
        'price_per_day' => 260.00,
        'mileage' => 9500,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE->value,
        'description' => 'Updated sportback specs.',
        'status' => CarStatus::AVAILABLE->value,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.cars.index'));
    $response->assertSessionHas('success', 'Car updated successfully.');

    $car->refresh();
    expect($car->model)->toBe('RS5 Sportback')
        ->and($car->color)->toBe(CarColor::BLACK)
        ->and((float) $car->price_per_day)->toBe(260.00)
        ->and($car->seats)->toBe(5);
});

it('allows admin to delete a vehicle', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $car = Car::create([
        'make' => 'Tesla',
        'model' => 'Model 3',
        'year' => 2024,
        'license_plate' => 'EV-9999',
        'color' => CarColor::WHITE->value,
        'price_per_day' => 120.00,
        'mileage' => 12000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::ELECTRIC->value,
        'status' => CarStatus::AVAILABLE->value,
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.cars.destroy', $car));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Car deleted successfully.');

    $this->assertSoftDeleted('cars', [
        'id' => $car->id,
    ]);
});

it('allows admin to suspend and activate a client', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $client = User::factory()->create([
        'role' => UserRole::CLIENT,
        'is_active' => true,
    ]);

    // Suspend client
    $suspendResponse = $this->actingAs($admin)->patch(route('admin.clients.suspend', $client));
    $suspendResponse->assertRedirect(route('admin.clients.show', $client));
    $suspendResponse->assertSessionHas('success', 'Client suspended successfully.');

    expect($client->refresh()->is_active)->toBeFalse();

    // Activate client
    $activateResponse = $this->actingAs($admin)->patch(route('admin.clients.activate', $client));
    $activateResponse->assertRedirect(route('admin.clients.show', $client));
    $activateResponse->assertSessionHas('success', 'Client activated successfully.');

    expect($client->refresh()->is_active)->toBeTrue();
});

it('protects primary demo accounts from deletion', function () {
    $demoAdmin = User::factory()->create([
        'email' => 'admin@example.com',
        'role' => UserRole::ADMIN,
    ]);

    $responseAdmin = $this->actingAs($demoAdmin)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $responseAdmin->assertRedirect();
    $responseAdmin->assertSessionHas('error', 'Primary demo accounts cannot be deleted to avoid lockout.');
    expect($demoAdmin->fresh())->not->toBeNull();

    $demoClient = User::factory()->create([
        'email' => 'client@example.com',
        'role' => UserRole::CLIENT,
    ]);

    $responseClient = $this->actingAs($demoClient)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $responseClient->assertRedirect();
    $responseClient->assertSessionHas('error', 'Primary demo accounts cannot be deleted to avoid lockout.');
    expect($demoClient->fresh())->not->toBeNull();
});

it('allows regular users to delete their account', function () {
    $regularUser = User::factory()->create([
        'email' => 'regular.user@example.com',
        'role' => UserRole::CLIENT,
    ]);

    $response = $this->actingAs($regularUser)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('home'));

    $this->assertGuest();
    expect($regularUser->fresh())->toBeNull();
});

it('allows admin to update a reservation status and dates', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $client = User::factory()->create(['role' => UserRole::CLIENT]);
    $car = Car::create([
        'make' => 'BMW',
        'model' => 'X5',
        'year' => 2024,
        'license_plate' => 'DL-99-ZZ-0001',
        'color' => CarColor::BLACK->value,
        'price_per_day' => 150.00,
        'mileage' => 10000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE->value,
        'status' => CarStatus::AVAILABLE->value,
    ]);

    $reservation = \App\Models\Reservation::create([
        'reservation_number' => 'RES-TEST-001',
        'car_id' => $car->id,
        'user_id' => $client->id,
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-05',
        'pickup_location' => 'Airport',
        'return_location' => 'Airport',
        'total_days' => 5,
        'daily_rate' => 150.00,
        'subtotal' => 750.00,
        'tax_amount' => 157.50,
        'discount_amount' => 0,
        'total_amount' => 907.50,
        'status' => \App\Enums\ReservationStatus::PENDING,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.reservations.update', $reservation), [
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-06',
        'pickup_time' => '10:00',
        'return_time' => '10:00',
        'pickup_location' => 'Airport Terminal 3',
        'return_location' => 'Airport Terminal 3',
        'status' => \App\Enums\ReservationStatus::CONFIRMED->value,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.reservations.show', $reservation));
    $response->assertSessionHas('success', 'Reservation updated successfully.');

    $reservation->refresh();
    expect($reservation->status)->toBe(\App\Enums\ReservationStatus::CONFIRMED)
        ->and($reservation->total_days)->toBe(6)
        ->and($reservation->pickup_location)->toBe('Airport Terminal 3');
});
