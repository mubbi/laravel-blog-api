<?php

declare(strict_types=1);

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

describe('UserRepository', function () {
    beforeEach(function () {
        $this->repository = app(UserRepositoryInterface::class);
    });

    describe('create', function () {
        it('can create a user', function () {
            // Arrange
            $data = [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => bcrypt('password'),
            ];

            // Act
            $result = $this->repository->create($data);

            // Assert
            expect($result)->toBeInstanceOf(User::class);
            expect($result->name)->toBe('John Doe');
            expect($result->email)->toBe('john@example.com');
            $this->assertDatabaseHas('users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });
    });

    describe('findById', function () {
        it('can find user by id', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->repository->findById($user->id);

            // Assert
            expect($result)->not->toBeNull();
            expect($result->id)->toBe($user->id);
        });

        it('returns null when user does not exist', function () {
            // Act
            $result = $this->repository->findById(99999);

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('findOrFail', function () {
        it('can find user by id or fail', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->repository->findOrFail($user->id);

            // Assert
            expect($result->id)->toBe($user->id);
        });

        it('throws exception when user does not exist', function () {
            // Act & Assert
            expect(fn () => $this->repository->findOrFail(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('update', function () {
        it('can update a user', function () {
            // Arrange
            $user = User::factory()->create(['name' => 'Old Name']);

            // Act
            $result = $this->repository->update($user->id, ['name' => 'New Name']);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'name' => 'New Name',
            ]);
        });
    });

    describe('delete', function () {
        it('can delete a user', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->repository->delete($user->id);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        });
    });

    describe('paginate', function () {
        it('can paginate users', function () {
            // Arrange
            $initialCount = User::count();
            User::factory()->count(20)->create();

            // Act
            $result = $this->repository->paginate(['per_page' => 10, 'page' => 1]);

            // Assert
            expect($result->count())->toBe(10);
            expect($result->total())->toBe($initialCount + 20);
        });
    });

    describe('all', function () {
        it('can get all users', function () {
            // Arrange
            $initialCount = User::count();
            User::factory()->count(5)->create();

            // Act
            $result = $this->repository->all();

            // Assert
            expect($result)->toHaveCount($initialCount + 5);
        });
    });

    describe('query', function () {
        it('returns query builder instance', function () {
            // Act
            $result = $this->repository->query();

            // Assert
            expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });
    });
});
