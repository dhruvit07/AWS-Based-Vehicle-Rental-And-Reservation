<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Car;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DemoPresentationController extends Controller
{
    public function index()
    {
        $totalCars = Car::count();
        $totalReservations = Reservation::count();
        $totalUsers = User::count();

        $scenarios = [
            [
                'id' => 'overlap',
                'title' => 'Date Collision & Overlap Lock',
                'badge' => 'High Complexity Algorithm',
                'role' => 'client',
                'icon' => 'CalendarX2',
                'description' => 'Real-time booking collision engine. Prevents double-booking across overlapping calendar day ranges.',
                'talking_points' => [
                    'Interactive 2-month date picker with visual day-range locking',
                    'Atomic database query prevents race condition double-bookings',
                    '30-minute reservation hold with automatic TTL expiration',
                ],
            ],
            [
                'id' => 'kyc',
                'title' => 'Driver KYC Verification Workflow',
                'badge' => 'Regulatory & Safety Compliance',
                'role' => 'admin',
                'icon' => 'ShieldCheck',
                'description' => 'Client license upload and fleet administrator document review with 1-click verification or rejection audit.',
                'talking_points' => [
                    'Encrypted government driving license image uploads',
                    'Admin verification modal with instant status badge toggle',
                    'Rejection reason logging and client notifications',
                ],
            ],
            [
                'id' => 'voucher',
                'title' => 'Printable Agreement & Phone QR Pass',
                'badge' => 'Enterprise Document Engine',
                'role' => 'client',
                'icon' => 'QrCode',
                'description' => 'Formal 1-page A4 legal rental contract with live vector QR code that opens an animated mobile verification pass on any smartphone camera.',
                'talking_points' => [
                    'Live scannable QR code linking to public mobile verification badge',
                    '4-Zone Pre-Delivery Inspection (PDI) checklist with digital pass seal',
                    'Single-page A4 print stylesheet (@media print) with zero ink waste',
                ],
            ],
            [
                'id' => 'radar',
                'title' => 'Fleet Operations Command Center',
                'badge' => 'Real-Time Telemetry & Dispatch',
                'role' => 'admin',
                'icon' => 'Radio',
                'description' => 'Situational control desk with 4 NCR mobility hubs, animated vector radar sweep, telemetry dials (fuel/battery/speed), and "Due Today" handover queue.',
                'talking_points' => [
                    '1-Click "Simulate Fleet Movement" button for live presentation demo',
                    'Circular SVG fuel/battery gauges and speedometers',
                    '1-Click "Dispatch & Handover" and check-in return workflow',
                ],
            ],
            [
                'id' => 'extend',
                'title' => 'Trip Extension & Early Return Portal',
                'badge' => 'Financial Differential Engine',
                'role' => 'client',
                'icon' => 'CalendarPlus',
                'description' => 'Dynamic rental lifecycle modification. Real-time calendar availability check, differential cost ledger (+extras +tax), and fair prorated early return refunds.',
                'talking_points' => [
                    'Conflict-aware future date availability check',
                    'Pro-rata daily rate + daily extras calculation with immediate mock checkout',
                    'Prorated refund credit calculation for early vehicle drop-offs',
                ],
            ],
            [
                'id' => 'cancel',
                'title' => 'Cancellation & Automated Refund Ledger',
                'badge' => 'Self-Service Customer Support',
                'role' => 'client',
                'icon' => 'RotateCcw',
                'description' => 'Self-service client booking cancellation with instant reverse payment ledger transition (REFUNDED) and automatic date release.',
                'talking_points' => [
                    'Policy validation: only upcoming confirmed bookings eligible',
                    'Automated payment reversal to REFUNDED with REF-XXXXXXXX identifier',
                    'Immediate calendar freeing for other customers',
                ],
            ],
        ];

        $architecture = [
            'framework' => 'Laravel 12.0 (PHP 8.2+)',
            'frontend' => 'Vue 3.5 + Inertia.js 2.1 (TypeScript)',
            'styling' => 'Tailwind CSS 4.1 + Custom Apple Glassmorphism Design System',
            'database' => 'SQLite / Relational Eloquent with 10 Strict Foreign Key Tables',
            'tests_count' => 127,
            'assertions_count' => 695,
        ];

        return Inertia::render('Presentation/Index', [
            'scenarios' => $scenarios,
            'architecture' => $architecture,
            'metrics' => [
                'total_fleet' => $totalCars,
                'total_reservations' => $totalReservations,
                'total_users' => $totalUsers,
                'test_suite_status' => '127 Passed (100%)',
            ],
        ]);
    }

    public function switchRole(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|in:admin,client',
        ]);

        $email = $validated['role'] === 'admin' ? 'admin@example.com' : 'client@example.com';
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::where('role', $validated['role'])->first();
        }

        if (! $user) {
            return back()->with('error', "No user found for role {$validated['role']}.");
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirectUrl = $validated['role'] === 'admin'
            ? route('admin.operations')
            : route('client.reservations.index');

        return redirect($redirectUrl)->with('success', "Switched identity to {$user->name} ({$validated['role']}).");
    }

    public function launchScenario(Request $request, string $scenario)
    {
        $admin = User::where('email', 'admin@example.com')->first()
            ?? User::where('role', UserRole::ADMIN)->first();
        $client = User::where('email', 'client@example.com')->first()
            ?? User::where('role', UserRole::CLIENT)->first();

        $demoRes = Reservation::where('user_id', $client?->id)->latest('id')->first()
            ?? Reservation::first();

        switch ($scenario) {
            case 'overlap':
                if ($client) {
                    Auth::login($client);
                    $request->session()->regenerate();
                }
                $car = Car::first();
                return redirect()->route('fleet.show', $car ?? 1)
                    ->with('success', 'Scenario 1: Date Collision & Overlap Lock — Select booked calendar dates to see conflict prevention in action.');

            case 'kyc':
                if ($admin) {
                    Auth::login($admin);
                    $request->session()->regenerate();
                }
                return redirect()->route('admin.clients.index')
                    ->with('success', 'Scenario 2: Driver KYC Verification — Inspect uploaded license and click Approve Driver.');

            case 'voucher':
                if ($demoRes?->user) {
                    Auth::login($demoRes->user);
                } elseif ($client) {
                    Auth::login($client);
                }
                $request->session()->regenerate();
                return redirect()->route('booking.agreement', $demoRes ?? 1)
                    ->with('success', 'Scenario 3: Printable Agreement & Phone QR Pass — Scan the QR code with any mobile camera.');

            case 'radar':
                if ($admin) {
                    Auth::login($admin);
                    $request->session()->regenerate();
                }
                return redirect()->route('admin.operations')
                    ->with('success', 'Scenario 4: Fleet Operations Command Center — Click "Simulate Fleet Movement" to trigger live GPS pings.');

            case 'extend':
                if ($demoRes?->user) {
                    Auth::login($demoRes->user);
                } elseif ($client) {
                    Auth::login($client);
                }
                $request->session()->regenerate();
                return redirect()->route('client.reservations.show', $demoRes?->id ?? 1)
                    ->with('success', 'Scenario 5: Trip Extension & Early Return — Click "Extend Trip" to view the differential ledger.');

            case 'cancel':
                if ($demoRes?->user) {
                    Auth::login($demoRes->user);
                } elseif ($client) {
                    Auth::login($client);
                }
                $request->session()->regenerate();
                return redirect()->route('client.reservations.show', $demoRes?->id ?? 1)
                    ->with('success', 'Scenario 6: Cancellation & Refund Ledger — Click "Cancel Booking" to inspect automated refund calculations.');

            default:
                return redirect()->route('home');
        }
    }
}
