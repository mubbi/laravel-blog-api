<?php

declare(strict_types=1);

use App\Models\NewsletterSubscriber;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\NewsletterSubscriberPolicy;

describe('NewsletterSubscriberPolicy', function () {
    beforeEach(function () {
        $this->policy = new NewsletterSubscriberPolicy;
    });

    describe('view', function () {
        it('allows user with view_newsletter_subscribers permission to view subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'view_newsletter_subscribers'],
                ['slug' => 'view_newsletter_subscribers']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $result = $this->policy->view($user, $subscriber);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without view_newsletter_subscribers permission to view subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $result = $this->policy->view($user, $subscriber);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows user with manage_newsletter_subscribers permission to create subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'manage_newsletter_subscribers'],
                ['slug' => 'manage_newsletter_subscribers']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeTrue();
        });

        it('allows user with subscribe_newsletter permission to create subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'subscribe_newsletter'],
                ['slug' => 'subscribe_newsletter']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without required permissions to create subscriber', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows user with manage_newsletter_subscribers permission to update subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'manage_newsletter_subscribers'],
                ['slug' => 'manage_newsletter_subscribers']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $result = $this->policy->update($user, $subscriber);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_newsletter_subscribers permission to update subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $result = $this->policy->update($user, $subscriber);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with manage_newsletter_subscribers permission to delete subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'manage_newsletter_subscribers'],
                ['slug' => 'manage_newsletter_subscribers']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $result = $this->policy->delete($user, $subscriber);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_newsletter_subscribers permission to delete subscriber', function () {
            // Arrange
            $user = User::factory()->create();
            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $result = $this->policy->delete($user, $subscriber);

            // Assert
            expect($result)->toBeFalse();
        });
    });
});
