<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\CommentPolicy;

describe('CommentPolicy', function () {
    beforeEach(function () {
        $this->policy = new CommentPolicy;
    });

    describe('view', function () {
        it('allows user with comment_moderate permission to view comment', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'comment_moderate'],
                ['slug' => 'comment_moderate']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $comment = Comment::factory()->create();

            // Act
            $result = $this->policy->view($user, $comment);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without comment_moderate permission to view comment', function () {
            // Arrange
            $user = User::factory()->create();
            $comment = Comment::factory()->create();

            // Act
            $result = $this->policy->view($user, $comment);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows any authenticated user to create comment', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeTrue();
        });

        it('allows user with edit_comments permission to create comment', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'edit_comments'],
                ['slug' => 'edit_comments']
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
    });

    describe('update', function () {
        it('allows user with edit_comments permission to update any comment', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'edit_comments'],
                ['slug' => 'edit_comments']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $comment = Comment::factory()->create();

            // Act
            $result = $this->policy->update($user, $comment);

            // Assert
            expect($result)->toBeTrue();
        });

        it('allows user to update their own comment without edit_comments permission', function () {
            // Arrange
            $user = User::factory()->create();
            $comment = Comment::factory()->create(['user_id' => $user->id]);

            // Act
            $result = $this->policy->update($user, $comment);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without edit_comments permission to update other users comment', function () {
            // Arrange
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $comment = Comment::factory()->create(['user_id' => $otherUser->id]);

            // Act
            $result = $this->policy->update($user, $comment);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with delete_comments permission to delete any comment', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'delete_comments'],
                ['slug' => 'delete_comments']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $comment = Comment::factory()->create();

            // Act
            $result = $this->policy->delete($user, $comment);

            // Assert
            expect($result)->toBeTrue();
        });

        it('allows user to delete their own comment without delete_comments permission', function () {
            // Arrange
            $user = User::factory()->create();
            $comment = Comment::factory()->create(['user_id' => $user->id]);

            // Act
            $result = $this->policy->delete($user, $comment);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without delete_comments permission to delete other users comment', function () {
            // Arrange
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $comment = Comment::factory()->create(['user_id' => $otherUser->id]);

            // Act
            $result = $this->policy->delete($user, $comment);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('report', function () {
        it('allows any authenticated user to report comment', function () {
            // Arrange
            $user = User::factory()->create();
            $comment = Comment::factory()->create();

            // Act
            $result = $this->policy->report($user, $comment);

            // Assert
            expect($result)->toBeTrue();
        });
    });
});
