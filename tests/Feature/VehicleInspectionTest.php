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
use App\Models\VehicleInspection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Inspector Admin',
        'email' => 'admin@example.com',
        'role' => UserRole::ADMIN,
    ]);

    $this->client = User::factory()->create([
        'name' => 'Renter Client',
        'email' => 'client@example.com',
        'role' => UserRole::CLIENT,
    ]);

    $this->otherClient = User::factory()->create([
        'name' => 'Other Client',
        'email' => 'other@example.com',
        'role' => UserRole::CLIENT,
    ]);

    $this->car = Car::create([
        'make' => 'Mercedes-Benz',
        'model' => 'C-Class',
        'year' => 2024,
        'license_plate' => 'DL-INSP-01',
        'color' => CarColor::WHITE,
        'price_per_day' => 7500.00,
        'mileage' => 12000,
        'transmission' => 'automatic',
        'seats' => 5,
        'fuel_type' => FuelType::GASOLINE,
        'status' => CarStatus::RENTED,
    ]);

    $this->reservation = Reservation::create([
        'reservation_number' => 'RES-INSP-001',
        'user_id' => $this->client->id,
        'car_id' => $this->car->id,
        'start_date' => Carbon::now()->subDays(3)->format('Y-m-d'),
        'end_date' => Carbon::now()->format('Y-m-d'),
        'pickup_time' => '10:00',
        'return_time' => '18:00',
        'pickup_location' => 'DEL-T3',
        'return_location' => 'DEL-T3',
        'total_days' => 3,
        'daily_rate' => 7500.00,
        'subtotal' => 22500.00,
        'tax_amount' => 4050.00,
        'discount_amount' => 0.00,
        'total_amount' => 26550.00,
        'status' => ReservationStatus::ACTIVE,
    ]);

    Payment::create([
        'reservation_id' => $this->reservation->id,
        'user_id' => $this->client->id,
        'amount' => 26550.00,
        'payment_method' => PaymentMethod::CREDIT_CARD,
        'status' => PaymentStatus::COMPLETED,
        'transaction_id' => 'TXN-INSP-001',
    ]);
});

it('processes pristine vehicle check-in with zero damage and full deposit refund', function () {
    $this->actingAs($this->admin);

    $response = $this->patch(route('admin.operations.checkin', $this->reservation->id), [
        'return_odometer' => 12150,
        'fuel_level' => 'full',
        'next_status' => 'cleaning',
        'condition_notes' => 'No dents or scratches detected. Clean cabin.',
        'damages' => [],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->reservation->refresh();
    expect($this->reservation->status)->toBe(ReservationStatus::COMPLETED);

    $this->car->refresh();
    expect($this->car->mileage)->toBe(12150);
    expect($this->car->status)->toBe(CarStatus::CLEANING);

    $inspection = VehicleInspection::where('reservation_id', $this->reservation->id)->first();
    expect($inspection)->not->toBeNull();
    expect($inspection->overall_condition)->toBe('pristine');
    expect((float) $inspection->standard_deposit)->toBe(5000.00);
    expect((float) $inspection->total_damage_cost)->toBe(0.00);
    expect((float) $inspection->deposit_deducted)->toBe(0.00);
    expect((float) $inspection->deposit_refunded)->toBe(5000.00);
    expect($inspection->settlement_status)->toBe('refunded_full');
});

it('processes vehicle check-in with minor damages and deducts repair costs from deposit', function () {
    $this->actingAs($this->admin);

    $damages = [
        [
            'id' => 'dmg-1',
            'zone' => 'front_bumper',
            'type' => 'scratch',
            'severity' => 'moderate',
            'cost' => 1500.00,
            'notes' => 'Lower lip scratch',
            'x' => 48,
            'y' => 11,
        ],
    ];

    $response = $this->patch(route('admin.operations.checkin', $this->reservation->id), [
        'return_odometer' => 12220,
        'fuel_level' => 'good',
        'next_status' => 'maintenance',
        'condition_notes' => 'Front bumper scuff reported.',
        'damages' => $damages,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->reservation->refresh();
    expect($this->reservation->status)->toBe(ReservationStatus::COMPLETED);

    $inspection = VehicleInspection::where('reservation_id', $this->reservation->id)->first();
    expect($inspection)->not->toBeNull();
    expect($inspection->overall_condition)->toBe('minor_wear');
    expect((float) $inspection->total_damage_cost)->toBe(1500.00);
    expect((float) $inspection->deposit_deducted)->toBe(1500.00);
    expect((float) $inspection->deposit_refunded)->toBe(3500.00);
    expect($inspection->settlement_status)->toBe('deducted_partial');
    expect($inspection->damages)->toHaveCount(1);
    expect($inspection->damages[0]['zone'])->toBe('front_bumper');
});

it('handles severe damage exceeding deposit by forfeiting full deposit and logging shortfall', function () {
    $this->actingAs($this->admin);

    $damages = [
        [
            'id' => 'dmg-rear',
            'zone' => 'rear_bumper',
            'type' => 'crack',
            'severity' => 'severe',
            'cost' => 6500.00,
            'notes' => 'Cracked rear bumper and taillight housing',
            'x' => 50,
            'y' => 90,
        ],
    ];

    $response = $this->patch(route('admin.operations.checkin', $this->reservation->id), [
        'return_odometer' => 12300,
        'fuel_level' => 'low',
        'next_status' => 'maintenance',
        'condition_notes' => 'Severe rear impact damage.',
        'damages' => $damages,
    ]);

    $response->assertSessionHasNoErrors();

    $inspection = VehicleInspection::where('reservation_id', $this->reservation->id)->first();
    expect($inspection)->not->toBeNull();
    expect($inspection->overall_condition)->toBe('severe');
    expect((float) $inspection->total_damage_cost)->toBe(6500.00);
    expect((float) $inspection->deposit_deducted)->toBe(5000.00);
    expect((float) $inspection->deposit_refunded)->toBe(0.00);
    expect($inspection->settlement_status)->toBe('shortfall_owed');
});

it('allows client to view completed inspection receipt on reservation show page', function () {
    // Complete the reservation with an inspection
    $this->reservation->update(['status' => ReservationStatus::COMPLETED]);

    VehicleInspection::create([
        'reservation_id' => $this->reservation->id,
        'car_id' => $this->car->id,
        'inspector_id' => $this->admin->id,
        'inspection_type' => 'checkin',
        'odometer' => 12180,
        'fuel_level' => 'full',
        'overall_condition' => 'minor_wear',
        'damages' => [
            [
                'id' => 'dmg-test',
                'zone' => 'left_front_door',
                'zone_label' => 'Front Left Door',
                'type' => 'scratch',
                'severity' => 'minor',
                'cost' => 800.00,
                'x' => 26,
                'y' => 42,
            ],
        ],
        'standard_deposit' => 5000.00,
        'total_damage_cost' => 800.00,
        'deposit_deducted' => 800.00,
        'deposit_refunded' => 4200.00,
        'settlement_status' => 'deducted_partial',
        'settlement_notes' => 'Minor door scratch detected.',
    ]);

    $this->actingAs($this->client);

    $response = $this->get(route('client.reservations.show', $this->reservation->id));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Client/Reservations/Show')
        ->has('reservation.inspection')
        ->where('reservation.inspection.settlement_status', 'deducted_partial')
        ->where('reservation.inspection.deposit_refunded', '4200.00')
    );
});

it('prevents non-owner client from viewing another users reservation and inspection', function () {
    $this->actingAs($this->otherClient);

    $response = $this->get(route('client.reservations.show', $this->reservation->id));

    $response->assertStatus(403);
});

it('allows admin to view reservation and inspection', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('admin.reservations.show', $this->reservation->id));

    $response->assertStatus(200);
});
