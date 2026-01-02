<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\ArticlePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ArticlePolicy', function () {
    beforeEach(function () {
        $this->policy = new ArticlePolicy;
    });

    describe('view', function () {
        it('allows user with view_posts permission to view article', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'view_posts'],
                ['slug' => 'view_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $article = Article::factory()->create();

            // Act
            $result = $this->policy->view($user, $article);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without view_posts permission to view article', function () {
            // Arrange
            $user = User::factory()->create();
            $article = Article::factory()->create();

            // Act
            $result = $this->policy->view($user, $article);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows user with publish_posts permission to create article', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'publish_posts'],
                ['slug' => 'publish_posts']
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

        it('denies user without publish_posts permission to create article', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows user with edit_others_posts permission to update any article', function () {
            // Arrange
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'edit_others_posts'],
                ['slug' => 'edit_others_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            // Act
            $result = $this->policy->update($user, $article);

            // Assert
            expect($result)->toBeTrue();
        });

        it('allows user with edit_posts permission to update own article', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $article = Article::factory()->create(['created_by' => $user->id]);

            // Act
            $result = $this->policy->update($user, $article);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user with edit_posts permission to update other users article', function () {
            // Arrange
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            // Act
            $result = $this->policy->update($user, $article);

            // Assert
            expect($result)->toBeFalse();
        });

        it('denies user without edit permissions to update article', function () {
            // Arrange
            $user = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $user->id]);

            // Act
            $result = $this->policy->update($user, $article);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with delete_others_posts permission to delete any article', function () {
            // Arrange
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'delete_others_posts'],
                ['slug' => 'delete_others_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            // Act
            $result = $this->policy->delete($user, $article);

            // Assert
            expect($result)->toBeTrue();
        });

        it('allows user with delete_posts permission to delete own article', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'delete_posts'],
                ['slug' => 'delete_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $article = Article::factory()->create(['created_by' => $user->id]);

            // Act
            $result = $this->policy->delete($user, $article);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user with delete_posts permission to delete other users article', function () {
            // Arrange
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'delete_posts'],
                ['slug' => 'delete_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $article = Article::factory()->create(['created_by' => $otherUser->id]);

            // Act
            $result = $this->policy->delete($user, $article);

            // Assert
            expect($result)->toBeFalse();
        });

        it('denies user without delete permissions to delete article', function () {
            // Arrange
            $user = User::factory()->create();
            $article = Article::factory()->create(['created_by' => $user->id]);

            // Act
            $result = $this->policy->delete($user, $article);

            // Assert
            expect($result)->toBeFalse();
        });
    });
});
