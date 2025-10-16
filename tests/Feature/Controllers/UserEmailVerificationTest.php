<?php

declare(strict_types=1);

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\URL;

it('may verify email', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Business::factory()->create(['owner_id' => $user->id]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)
        ->fromRoute('verification.notice')
        ->get($verificationUrl);

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();

    $response->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
});

it('redirects to dashboard if already verified', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Business::factory()->create(['owner_id' => $user->id]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)
        ->fromRoute('verification.notice')
        ->get($verificationUrl);

    $response->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
});

it('requires valid signature', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $invalidUrl = route('verification.verify', [
        'id' => $user->getKey(),
        'hash' => sha1($user->email),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('verification.notice')
        ->get($invalidUrl);

    $response->assertForbidden();
});
