<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CarStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Reservation;
use App\Models\VehicleInspection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OperationsController extends Controller
{
    public const HUBS = [
        [
            'id' => 'hub-alpha',
            'code' => 'DEL-T3',
            'name' => 'IGI Airport Terminal 3',
            'city' => 'New Delhi',
            'capacity' => 24,
            'x' => 26,
            'y' => 38,
            'type' => 'Airport Express Hub',
        ],
        [
            'id' => 'hub-bravo',
            'code' => 'DEL-CP',
            'name' => 'Connaught Place Central',
            'city' => 'Central Delhi',
            'capacity' => 16,
            'x' => 54,
            'y' => 28,
            'type' => 'Downtown Business Depot',
        ],
        [
            'id' => 'hub-charlie',
            'code' => 'GGN-CYB',
            'name' => 'Cyber City Tech Corridor',
            'city' => 'Gurugram',
            'capacity' => 18,
            'x' => 76,
            'y' => 64,
            'type' => 'Corporate Mobility Station',
        ],
        [
            'id' => 'hub-delta',
            'code' => 'DEL-AER',
            'name' => 'Aerocity Fleet Depot & Service Hub',
            'city' => 'South West Delhi',
            'capacity' => 30,
            'x' => 38,
            'y' => 74,
            'type' => 'Central Maintenance Depot',
        ],
    ];

    public function index()
    {
        $cars = Car::with(['reservations' => function ($q) {
            $q->whereIn('status', [ReservationStatus::CONFIRMED, ReservationStatus::ACTIVE])
                ->with('user:id,name,email,license_number,loyalty_tier')
                ->latest();
        }])->get();

        $totalCars = $cars->count();
        $onRoadCount = $cars->where('status', CarStatus::RENTED)->count();
        $availableCount = $cars->where('status', CarStatus::AVAILABLE)->count();
        $maintenanceCount = $cars->whereIn('status', [CarStatus::MAINTENANCE, CarStatus::CLEANING])->count();
        $utilizationRate = $totalCars > 0 ? round(($onRoadCount / $totalCars) * 100, 1) : 0;

        // Pickups Due (CONFIRMED reservations)
        $pickupsDue = Reservation::where('status', ReservationStatus::CONFIRMED)
            ->with(['user', 'car', 'payments'])
            ->orderBy('start_date')
            ->take(10)
            ->get();

        // Returns Due (ACTIVE reservations)
        $returnsDue = Reservation::where('status', ReservationStatus::ACTIVE)
            ->with(['user', 'car', 'payments'])
            ->orderBy('end_date')
            ->take(10)
            ->get();

        // Decorate cars with realistic simulated telemetry
        $fleetWithTelemetry = $cars->map(function ($car, $index) {
            $isRented = $car->status === CarStatus::RENTED;
            $hubIndex = $car->id % count(self::HUBS);
            $hub = self::HUBS[$hubIndex];

            // Deterministic jitter around hub or en route
            $jitterX = (($car->id * 17) % 18) - 9;
            $jitterY = (($car->id * 23) % 18) - 9;

            $mapX = $isRented ? min(90, max(10, $hub['x'] + $jitterX * 1.8)) : ($hub['x'] + $jitterX * 0.6);
            $mapY = $isRented ? min(90, max(10, $hub['y'] + $jitterY * 1.8)) : ($hub['y'] + $jitterY * 0.6);

            $fuelLevel = match ($car->status) {
                CarStatus::RENTED => max(32, 88 - (($car->id * 7) % 45)),
                CarStatus::MAINTENANCE => 40,
                CarStatus::CLEANING => 95,
                default => 100,
            };

            $speedKmh = $isRented ? (38 + (($car->id * 11) % 46)) : 0;

            $activeReservation = $car->reservations->first();

            return [
                'id' => $car->id,
                'make' => $car->make,
                'model' => $car->model,
                'year' => $car->year,
                'license_plate' => $car->license_plate,
                'color' => $car->color?->value ?? (string) $car->color,
                'status' => $car->status->value,
                'status_label' => $car->status->label(),
                'status_color' => $car->status->color(),
                'mileage' => (int) $car->mileage,
                'fuel_type' => $car->fuel_type?->value ?? (string) $car->fuel_type,
                'transmission' => $car->transmission,
                'seats' => $car->seats,
                'price_per_day' => (float) $car->price_per_day,
                'telemetry' => [
                    'fuel_level' => $fuelLevel,
                    'is_electric' => strtolower((string) ($car->fuel_type?->value ?? $car->fuel_type)) === 'electric',
                    'battery_health' => 95 + ($car->id % 5),
                    'tire_pressure_psi' => 32 + ($car->id % 3),
                    'engine_health' => 'Optimal (98%)',
                    'speed_kmh' => $speedKmh,
                    'is_moving' => $speedKmh > 0,
                    'last_ping' => ($car->id % 45 + 5) . 's ago',
                    'hub_name' => $hub['name'],
                    'hub_code' => $hub['code'],
                    'map_x' => round($mapX, 1),
                    'map_y' => round($mapY, 1),
                ],
                'active_reservation' => $activeReservation ? [
                    'id' => $activeReservation->id,
                    'reservation_number' => $activeReservation->reservation_number,
                    'status' => $activeReservation->status->value,
                    'start_date' => $activeReservation->start_date?->format('Y-m-d'),
                    'end_date' => $activeReservation->end_date?->format('Y-m-d'),
                    'user_name' => $activeReservation->user?->name ?? 'Verified Hirer',
                    'user_license' => $activeReservation->user?->license_number ?? 'DL-VERIFIED',
                ] : null,
            ];
        });

        return Inertia::render('Admin/Operations/Index', [
            'kpis' => [
                'total_fleet' => $totalCars,
                'on_road' => $onRoadCount,
                'available' => $availableCount,
                'maintenance' => $maintenanceCount,
                'utilization_rate' => $utilizationRate,
                'pickups_due_count' => $pickupsDue->count(),
                'returns_due_count' => $returnsDue->count(),
            ],
            'hubs' => self::HUBS,
            'fleet' => $fleetWithTelemetry,
            'pickupsDue' => $pickupsDue,
            'returnsDue' => $returnsDue,
            'carStatusOptions' => array_map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ], CarStatus::cases()),
        ]);
    }

    public function dispatch(Reservation $reservation)
    {
        if ($reservation->status !== ReservationStatus::CONFIRMED) {
            return back()->with('error', 'Only confirmed reservations can be dispatched.');
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status' => ReservationStatus::ACTIVE,
            ]);

            if ($reservation->car) {
                $reservation->car->update([
                    'status' => CarStatus::RENTED,
                ]);
            }
        });

        $reservation->load(['car', 'user']);
        $carDesc = $reservation->car ? "{$reservation->car->make} {$reservation->car->model}" : 'Vehicle';
        $renter = $reservation->user?->name ?? 'Hirer';

        return back()->with('success', "Handover complete: {$carDesc} successfully dispatched to {$renter}.");
    }

    public function checkin(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== ReservationStatus::ACTIVE) {
            return back()->with('error', 'Only active reservations can be checked in.');
        }

        $currentMileage = (int) ($reservation->car?->mileage ?? 0);

        $validated = $request->validate([
            'return_odometer' => ['required', 'integer', 'min:' . $currentMileage],
            'fuel_level' => ['required', 'string', 'in:full,good,low,empty'],
            'next_status' => ['required', 'string', 'in:available,cleaning,maintenance'],
            'condition_notes' => ['nullable', 'string', 'max:1000'],
            'damages' => ['nullable', 'array'],
            'damages.*.id' => ['nullable', 'string'],
            'damages.*.zone' => ['required_with:damages', 'string'],
            'damages.*.type' => ['required_with:damages', 'string'],
            'damages.*.severity' => ['required_with:damages', 'string'],
            'damages.*.cost' => ['required_with:damages', 'numeric', 'min:0'],
            'damages.*.notes' => ['nullable', 'string'],
            'damages.*.x' => ['nullable', 'numeric'],
            'damages.*.y' => ['nullable', 'numeric'],
        ]);

        $standardDeposit = 5000.00;
        $damagesList = $validated['damages'] ?? [];
        $totalDamageCost = 0.00;
        foreach ($damagesList as $damage) {
            $totalDamageCost += (float) ($damage['cost'] ?? 0);
        }
        $totalDamageCost = round($totalDamageCost, 2);

        if ($totalDamageCost <= 0.00) {
            $overallCondition = 'pristine';
            $depositDeducted = 0.00;
            $depositRefunded = $standardDeposit;
            $settlementStatus = 'refunded_full';
            $settlementNotes = 'Pristine return. Full security deposit (₹5,000) released to customer.';
        } elseif ($totalDamageCost <= $standardDeposit) {
            $overallCondition = count($damagesList) > 2 ? 'damaged' : 'minor_wear';
            $depositDeducted = $totalDamageCost;
            $depositRefunded = round($standardDeposit - $totalDamageCost, 2);
            $settlementStatus = 'deducted_partial';
            $settlementNotes = "Repair deductions of ₹" . number_format($depositDeducted, 2) . " applied against security deposit. Net refund of ₹" . number_format($depositRefunded, 2) . " processed to customer.";
        } else {
            $overallCondition = 'severe';
            $depositDeducted = $standardDeposit;
            $depositRefunded = 0.00;
            $settlementStatus = 'shortfall_owed';
            $shortfall = round($totalDamageCost - $standardDeposit, 2);
            $settlementNotes = "Damage repair costs (₹" . number_format($totalDamageCost, 2) . ") exceeded security deposit. Entire deposit (₹5,000) forfeited. Outstanding shortfall of ₹" . number_format($shortfall, 2) . " logged.";
        }

        DB::transaction(function () use ($reservation, $validated, $damagesList, $standardDeposit, $totalDamageCost, $depositDeducted, $depositRefunded, $settlementStatus, $settlementNotes, $overallCondition) {
            $reservation->update([
                'status' => ReservationStatus::COMPLETED,
                'notes' => trim(($reservation->notes ? $reservation->notes . "\n" : '') . "Return Check-in: Odometer {$validated['return_odometer']} km, Fuel: {$validated['fuel_level']}. " . ($validated['condition_notes'] ?? '') . ($totalDamageCost > 0 ? " [Damage Assessment: ₹{$totalDamageCost}]" : ' [Pristine Return]')),
            ]);

            VehicleInspection::create([
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'inspector_id' => Auth::id() ?? $reservation->user_id,
                'inspection_type' => 'checkin',
                'odometer' => $validated['return_odometer'],
                'fuel_level' => $validated['fuel_level'],
                'overall_condition' => $overallCondition,
                'damages' => $damagesList,
                'standard_deposit' => $standardDeposit,
                'total_damage_cost' => $totalDamageCost,
                'deposit_deducted' => $depositDeducted,
                'deposit_refunded' => $depositRefunded,
                'settlement_status' => $settlementStatus,
                'settlement_notes' => $settlementNotes,
            ]);

            if ($reservation->car) {
                $nextStatus = CarStatus::tryFrom($validated['next_status']) ?? CarStatus::AVAILABLE;
                $reservation->car->update([
                    'status' => $nextStatus,
                    'mileage' => $validated['return_odometer'],
                ]);
            }
        });

        $reservation->load('car');
        $carDesc = $reservation->car ? "{$reservation->car->make} {$reservation->car->model}" : 'Vehicle';
        $summaryMsg = $totalDamageCost > 0
            ? "Check-in complete: {$carDesc} inspected. ₹" . number_format($totalDamageCost, 2) . " damage logged; ₹" . number_format($depositRefunded, 2) . " deposit balance refunded."
            : "Check-in complete: {$carDesc} inspected in pristine condition. Full ₹5,000 deposit refunded.";

        return back()->with('success', $summaryMsg);
    }

    public function updateCarStatus(Request $request, Car $car)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:available,maintenance,cleaning,unavailable,retired,reserved,rented'],
        ]);

        $newStatus = CarStatus::tryFrom($validated['status']);
        if (! $newStatus) {
            return back()->with('error', 'Invalid vehicle status selected.');
        }

        $car->update(['status' => $newStatus]);

        return back()->with('success', "{$car->make} {$car->model} status updated to {$newStatus->label()}.");
    }
}
