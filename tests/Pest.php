<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;

// Enable bypass-finals to allow mocking final classes in tests
DG\BypassFinals::enable();

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Attach a role to a user and clear their cache to ensure permissions are refreshed
 */
function attachRoleAndRefreshCache(User $user, Role $role): void
{
    $user->roles()->attach($role->id);
    $user->refresh();
    $user->load('roles.permissions');
    $user->clearCache();
}

/**
 * Get a role by name from the database
 */
function getRoleByName(string $roleName): Role
{
    /** @var Role $role */
    $role = Role::where('name', $roleName)->first();

    return $role;
}

/**
 * Create a user with a specific role attached and cache refreshed
 */
function createUserWithRole(string $roleName): User
{
    $user = User::factory()->create();
    $role = getRoleByName($roleName);
    attachRoleAndRefreshCache($user, $role);

    return $user;
}

/**
 * Create a fake image file for testing without requiring GD extension
 * Returns a minimal valid JPEG file content
 */
function createFakeImageFile(string $filename = 'test-image.jpg', int $width = 800, int $height = 600): \Illuminate\Http\UploadedFile
{
    // Create minimal valid JPEG content (JPEG SOI + APP0 marker + EOI marker)
    // This creates a valid JPEG structure that will pass basic MIME type validation
    $jpegContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00";
    $jpegContent .= "\xFF\xD9"; // JPEG EOI marker

    // Use createWithContent if available (Laravel 10+)
    $factory = \Illuminate\Http\UploadedFile::fake();
    if (method_exists($factory, 'createWithContent')) {
        return $factory->createWithContent($filename, $jpegContent);
    }

    // Fallback: use create() with MIME type
    return $factory->create($filename, 10, 'image/jpeg');
}
