<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientsController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $query = User::query()
            ->where('role', UserRole::CLIENT)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($q) use ($status) {
                if ($status === 'active') {
                    $q->where('is_active', true);
                } elseif ($status === 'suspended') {
                    $q->where('is_active', false);
                }
            })
            ->withCount(['reservations', 'payments'])
            ->orderBy('name');

        $clients = $query->paginate(10)->withQueryString();

        $statusCounts = [
            'active' => User::where('role', UserRole::CLIENT)->where('is_active', true)->count(),
            'suspended' => User::where('role', UserRole::CLIENT)->where('is_active', false)->count(),
        ];

        $statuses = [
            'active' => ['label' => 'Active', 'count' => $statusCounts['active'], 'color' => '#10B981'],
            'suspended' => ['label' => 'Suspended', 'count' => $statusCounts['suspended'], 'color' => '#EF4444'],
        ];

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => $statuses,
        ]);
    }

    public function show(User $client): Response
    {
        $totalSpent = Payment::where('user_id', $client->id)
            ->where('status', PaymentStatus::COMPLETED)
            ->sum('amount');

        $reservations = $client->reservations()
            ->with(['car'])
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'reservations_page')
            ->withQueryString();

        $payments = $client->payments()
            ->with(['reservation'])
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'payments_page')
            ->withQueryString();

        return Inertia::render('Admin/Clients/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'is_active' => (bool) $client->is_active,
                'created_at' => $client->created_at,
                'kyc_status' => $client->kyc_status ?? 'unsubmitted',
                'license_number' => $client->license_number,
                'license_expiry' => $client->license_expiry?->format('Y-m-d'),
                'license_front_url' => $client->license_front_url,
                'license_back_url' => $client->license_back_url,
                'kyc_rejection_reason' => $client->kyc_rejection_reason,
                'kyc_verified_at' => $client->kyc_verified_at?->toIso8601String(),
            ],
            'stats' => [
                'total_reservations' => $client->reservations()->count(),
                'total_payments' => $client->payments()->count(),
                'total_spent' => (float) $totalSpent,
            ],
            'reservations' => $reservations,
            'payments' => $payments,
        ]);
    }

    public function suspend(User $client)
    {
        $client->is_active = false;
        $client->save();

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client suspended successfully.');
    }

    public function activate(User $client)
    {
        $client->is_active = true;
        $client->save();

        return redirect()
            ->route('admin.clients.show', $client)
            ->with('success', 'Client activated successfully.');
    }

    public function approveKyc(User $client)
    {
        $client->update([
            'kyc_status' => 'verified',
            'kyc_verified_at' => now(),
            'kyc_rejection_reason' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Client driver license has been approved.');
    }

    public function rejectKyc(Request $request, User $client)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $client->update([
            'kyc_status' => 'rejected',
            'kyc_rejection_reason' => $validated['rejection_reason'],
            'kyc_verified_at' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Client driver license has been rejected with feedback.');
    }
}
