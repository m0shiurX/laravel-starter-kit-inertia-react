<?php

declare(strict_types=1);

use App\Actions\AssignBusinessRoleAction;
use App\Actions\CreateBusinessAction;
use App\Actions\DeleteBusinessAction;
use App\Actions\InviteBusinessMemberAction;
use App\Actions\RemoveBusinessMemberAction;
use App\Actions\SwitchBusinessAction;
use App\Actions\UpdateBusinessAction;
use App\Data\BusinessData;
use App\Models\Business;
use App\Models\User;
use App\Services\TenantResolver;

use function Pest\Laravel\assertDatabaseMissing;

test('user can create a business', function (): void {
    $user = User::factory()->create();
    $data = BusinessData::from(['name' => 'Acme Corp']);

    $action = new CreateBusinessAction();
    $business = $action->handle($user, $data);

    expect($business)->toBeInstanceOf(Business::class)
        ->and($business->name)->toBe('Acme Corp')
        ->and($business->owner_id)->toBe($user->id)
        ->and($business->hasMember($user))->toBeTrue();

    // User should have owner role
    expect($user->hasBusinessRole('owner', $business))->toBeTrue();
});

test('user can update a business', function (): void {
    $user = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $user->id]);

    $data = BusinessData::from(['name' => 'Updated Name']);
    $action = new UpdateBusinessAction();
    $updated = $action->handle($business, $data);

    expect($updated->name)->toBe('Updated Name');
});

test('user can delete a business', function (): void {
    $user = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $user->id]);
    $business->users()->attach($user);

    $action = new DeleteBusinessAction();
    $action->handle($business);

    assertDatabaseMissing('businesses', ['id' => $business->id]);
    assertDatabaseMissing('business_user', ['business_id' => $business->id]);
});

test('user can switch business context', function (): void {
    $user = User::factory()->create();
    $business1 = Business::factory()->create(['owner_id' => $user->id]);
    $business2 = Business::factory()->create(['owner_id' => $user->id]);
    $business1->users()->attach($user);
    $business2->users()->attach($user);

    $resolver = app(TenantResolver::class);
    $action = new SwitchBusinessAction($resolver);

    $action->handle($user, $business2);

    expect($resolver->getCurrentBusiness()?->id)->toBe($business2->id);
});

test('cannot switch to business without access', function (): void {
    $user = User::factory()->create();
    $otherBusiness = Business::factory()->create();

    $resolver = app(TenantResolver::class);
    $action = new SwitchBusinessAction($resolver);

    $action->handle($user, $otherBusiness);
})->throws(InvalidArgumentException::class);

test('user can invite member to business', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $action = new InviteBusinessMemberAction();
    $action->handle($business, $member, 'manager');

    expect($business->hasMember($member))->toBeTrue()
        ->and($member->hasBusinessRole('manager', $business))->toBeTrue();
});

test('user can remove member from business', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $business->users()->attach($member);

    $action = new RemoveBusinessMemberAction();
    $action->handle($business, $member);

    expect($business->hasMember($member))->toBeFalse();
});

test('cannot remove business owner', function (): void {
    $owner = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $business->users()->attach($owner);

    $action = new RemoveBusinessMemberAction();
    $action->handle($business, $owner);
})->throws(InvalidArgumentException::class);

test('can assign role to business member', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $business->users()->attach($member);

    $action = new AssignBusinessRoleAction();
    $action->handle($business, $member, 'admin');

    expect($member->hasBusinessRole('admin', $business))->toBeTrue();
});

test('cannot assign owner role', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $business->users()->attach($member);

    $action = new AssignBusinessRoleAction();
    $action->handle($business, $member, 'owner');
})->throws(InvalidArgumentException::class);
