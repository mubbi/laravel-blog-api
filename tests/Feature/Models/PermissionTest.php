<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;

describe('Permission Model', function () {
    it('can be created', function () {
        // Act
        $permission = Permission::factory()->create([
            'name' => 'Edit Posts',
            'slug' => 'edit_posts',
        ]);

        // Assert
        expect($permission->name)->toBe('Edit Posts');
        expect($permission->slug)->toBe('edit_posts');
        expect($permission->id)->toBeInt();
    });

    it('has roles relationship', function () {
        // Arrange
        $permission = Permission::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $permission->roles()->attach([$role1->id, $role2->id]);

        // Act
        $roles = $permission->roles;

        // Assert
        expect($roles)->toHaveCount(2);
        expect($roles->pluck('id')->toArray())->toContain($role1->id, $role2->id);
    });

    it('can attach roles to permission', function () {
        // Arrange
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();

        // Act
        $permission->roles()->attach($role->id);

        // Assert
        expect($permission->roles)->toHaveCount(1);
        expect($permission->roles->first()->id)->toBe($role->id);
    });

    it('can detach roles from permission', function () {
        // Arrange
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $permission->roles()->attach($role->id);

        // Act
        $permission->roles()->detach($role->id);

        // Assert
        expect($permission->fresh()->roles)->toHaveCount(0);
    });

    it('has timestamps', function () {
        // Arrange
        $permission = Permission::factory()->create();

        // Assert
        expect($permission->created_at)->not->toBeNull();
        expect($permission->updated_at)->not->toBeNull();
    });
});
