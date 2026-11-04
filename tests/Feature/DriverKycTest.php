<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('redirects guests away from kyc settings', function () {
    $response = $this->get(route('kyc.show'));
    $response->assertRedirect(route('login'));
});

it('allows client to view kyc settings page', function () {
    $client = User::factory()->create([
        'role' => UserRole::CLIENT,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($client)->get(route('kyc.show'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/Kyc')
        ->has('kyc', fn (Assert $kyc) => $kyc
            ->where('status', 'unsubmitted')
            ->where('license_number', null)
            ->where('license_expiry', null)
            ->where('license_front_url', null)
            ->where('license_back_url', null)
            ->where('rejection_reason', null)
            ->where('verified_at', null)
        )
    );
});

it('allows client to upload license details and sets status to pending', function () {
    Storage::fake('public');

    $client = User::factory()->create([
        'role' => UserRole::CLIENT,
        'is_active' => true,
        'email_verified_at' => now(),
        'kyc_status' => 'unsubmitted',
    ]);

    $response = $this->actingAs($client)->post(route('kyc.store'), [
        'license_number' => 'DL-9876543210',
        'license_expiry' => '2028-12-31',
        'front_image' => UploadedFile::fake()->image('front.jpg', 800, 600),
        'back_image' => UploadedFile::fake()->image('back.jpg', 800, 600),
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $client->refresh();

    expect($client->kyc_status)->toBe('pending')
        ->and($client->license_number)->toBe('DL-9876543210')
        ->and($client->license_expiry->format('Y-m-d'))->toBe('2028-12-31')
        ->and($client->isKycPending())->toBeTrue()
        ->and($client->isKycVerified())->toBeFalse()
        ->and($client->license_front_image)->not->toBeNull()
        ->and($client->license_back_image)->not->toBeNull()
        ->and($client->license_front_url)->not->toBeNull()
        ->and($client->license_back_url)->not->toBeNull();

    Storage::disk('public')->assertExists($client->license_front_image);
    Storage::disk('public')->assertExists($client->license_back_image);
});

it('allows admin to approve client kyc verification', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $client = User::factory()->create([
        'role' => UserRole::CLIENT,
        'is_active' => true,
        'email_verified_at' => now(),
        'kyc_status' => 'pending',
        'license_number' => 'DL-11223344',
        'kyc_rejection_reason' => 'Previously rejected',
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.clients.kyc.approve', $client));

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $client->refresh();

    expect($client->kyc_status)->toBe('verified')
        ->and($client->isKycVerified())->toBeTrue()
        ->and($client->isKycPending())->toBeFalse()
        ->and($client->kyc_verified_at)->not->toBeNull()
        ->and($client->kyc_rejection_reason)->toBeNull();
});

it('allows admin to reject client kyc with a reason', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $client = User::factory()->create([
        'role' => UserRole::CLIENT,
        'is_active' => true,
        'email_verified_at' => now(),
        'kyc_status' => 'pending',
        'kyc_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.clients.kyc.reject', $client), [
        'rejection_reason' => 'Front photo is blurry and illegible.',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $client->refresh();

    expect($client->kyc_status)->toBe('rejected')
        ->and($client->kyc_rejection_reason)->toBe('Front photo is blurry and illegible.')
        ->and($client->kyc_verified_at)->toBeNull()
        ->and($client->isKycVerified())->toBeFalse();
});

it('prevents non-admin client from approving or rejecting kyc', function () {
    $client = User::factory()->create([
        'role' => UserRole::CLIENT,
        'is_active' => true,
        'email_verified_at' => now(),
    ]);

    $otherClient = User::factory()->create([
        'role' => UserRole::CLIENT,
        'is_active' => true,
        'email_verified_at' => now(),
        'kyc_status' => 'pending',
    ]);

    $responseApprove = $this->actingAs($client)->patch(route('admin.clients.kyc.approve', $otherClient));
    $responseApprove->assertForbidden();

    $responseReject = $this->actingAs($client)->patch(route('admin.clients.kyc.reject', $otherClient), [
        'rejection_reason' => 'Unauthorized action',
    ]);
    $responseReject->assertForbidden();
});
