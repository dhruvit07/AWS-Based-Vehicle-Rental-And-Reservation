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

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Demo Administrator',
        'email' => 'admin@example.com',
        'role' => UserRole::ADMIN,
    ]);

    $this->client = User::factory()->create([
        'name' => 'Demo Client',
        'email' => 'client@example.com',
        'role' => UserRole::CLIENT,
        'license_number' => 'DL-99998888',
        'kyc_status' => 'verified',
    ]);

    $this->car = Car::create([
        'make' => 'BMW',
        'model' => 'M4 Competition',
        'year' => 2024,
        'license_plate' => 'DL-DEMO-01',
        'color' => CarColor::BLACK,
        'price_per_day' => 12000.00,
        'mileage' => 4500,
        'transmission' => 'automatic',
        'seats' => 4,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::AVAILABLE,
    ]);

    $this->reservation = Reservation::create([
        'reservation_number' => 'RES-DEMO-001',
        'user_id' => $this->client->id,
        'car_id' => $this->car->id,
        'start_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
        'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'total_days' => 3,
        'daily_rate' => 12000.00,
        'subtotal' => 36000.00,
        'tax_amount' => 6480.00,
        'discount_amount' => 0.00,
        'total_amount' => 42480.00,
        'status' => ReservationStatus::CONFIRMED,
    ]);

    Payment::create([
        'reservation_id' => $this->reservation->id,
        'user_id' => $this->client->id,
        'amount' => 42480.00,
        'payment_method' => PaymentMethod::CREDIT_CARD,
        'status' => PaymentStatus::COMPLETED,
        'transaction_id' => 'TXN-DEMO-001',
    ]);
});

it('renders presentation deck with metrics and scenarios', function () {
    $response = $this->get(route('demo.presentation'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Presentation/Index')
        ->has('scenarios', 7)
        ->has('architecture')
        ->has('metrics')
        ->where('metrics.total_fleet', 1)
        ->where('metrics.total_reservations', 1)
        ->where('metrics.total_users', 2)
    );
});

it('switches role to admin and redirects to operations', function () {
    $this->actingAs($this->client);

    $response = $this->post(route('demo.switchRole'), [
        'role' => 'admin',
    ]);

    $response->assertRedirect(route('admin.operations'));
    $this->assertAuthenticatedAs($this->admin);
});

it('switches role to client and redirects to reservations', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('demo.switchRole'), [
        'role' => 'client',
    ]);

    $response->assertRedirect(route('client.reservations.index'));
    $this->assertAuthenticatedAs($this->client);
});

it('rejects invalid role switch', function () {
    $response = $this->post(route('demo.switchRole'), [
        'role' => 'superadmin',
    ]);

    $response->assertSessionHasErrors(['role']);
});

it('launches overlap scenario and redirects to car details', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'overlap']));

    $response->assertRedirect(route('fleet.show', $this->car->id));
    $this->assertAuthenticatedAs($this->client);
});

it('launches kyc scenario as admin and redirects to clients index', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'kyc']));

    $response->assertRedirect(route('admin.clients.index'));
    $this->assertAuthenticatedAs($this->admin);
});

it('launches voucher scenario as client and redirects to agreement', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'voucher']));

    $response->assertRedirect(route('booking.agreement', $this->reservation->id));
    $this->assertAuthenticatedAs($this->client);
});

it('launches radar scenario as admin and redirects to operations', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'radar']));

    $response->assertRedirect(route('admin.operations'));
    $this->assertAuthenticatedAs($this->admin);
});

it('launches extend scenario as client and redirects to reservation show', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'extend']));

    $response->assertRedirect(route('client.reservations.show', $this->reservation->id));
    $this->assertAuthenticatedAs($this->client);
});

it('launches cancel scenario as client and redirects to reservation show', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'cancel']));

    $response->assertRedirect(route('client.reservations.show', $this->reservation->id));
    $this->assertAuthenticatedAs($this->client);
});

it('launches damage scenario as admin and redirects to operations', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'damage']));

    $response->assertRedirect(route('admin.operations'));
    $this->assertAuthenticatedAs($this->admin);
});

it('handles unknown scenario by redirecting to home', function () {
    $response = $this->post(route('demo.launchScenario', ['scenario' => 'nonexistent']));

    $response->assertRedirect(route('home'));
});
