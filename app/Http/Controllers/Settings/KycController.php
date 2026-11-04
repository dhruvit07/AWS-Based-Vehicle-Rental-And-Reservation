<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class KycController extends Controller
{
    /**
     * Show the user's KYC verification settings page.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->role === \App\Enums\UserRole::ADMIN) {
            return redirect()->route('profile.edit')->with('restricted_action', 'Driver License verification is only for clients. As an administrator, you manage and verify client licenses in the Clients panel.');
        }

        return Inertia::render('settings/Kyc', [
            'kyc' => [
                'status' => $user->kyc_status ?? 'unsubmitted',
                'license_number' => $user->license_number,
                'license_expiry' => $user->license_expiry?->format('Y-m-d'),
                'license_front_url' => $user->license_front_url,
                'license_back_url' => $user->license_back_url,
                'rejection_reason' => $user->kyc_rejection_reason,
                'verified_at' => $user->kyc_verified_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Store or update the user's KYC driving credentials.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === \App\Enums\UserRole::ADMIN) {
            abort(403, 'Admins cannot submit driver licenses.');
        }

        $validated = $request->validate([
            'license_number' => ['required', 'string', 'max:50'],
            'license_expiry' => ['required', 'date'],
            'front_image' => [$user->license_front_image ? 'nullable' : 'required', 'file', 'image', 'max:5120'],
            'back_image' => [$user->license_back_image ? 'nullable' : 'required', 'file', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('front_image')) {
            if ($user->license_front_image && Storage::disk('public')->exists($user->license_front_image)) {
                Storage::disk('public')->delete($user->license_front_image);
            }
            $user->license_front_image = $request->file('front_image')->store('kyc', 'public');
        }

        if ($request->hasFile('back_image')) {
            if ($user->license_back_image && Storage::disk('public')->exists($user->license_back_image)) {
                Storage::disk('public')->delete($user->license_back_image);
            }
            $user->license_back_image = $request->file('back_image')->store('kyc', 'public');
        }

        $user->license_number = $validated['license_number'];
        $user->license_expiry = $validated['license_expiry'];
        $user->kyc_status = 'pending';
        $user->kyc_rejection_reason = null;
        $user->save();

        return redirect()->back()->with('success', 'Your driving credentials have been submitted for verification.');
    }
}
