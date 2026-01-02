<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\CacheKeys;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class ClearRolePermissionCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-roles-permissions {--user-id= : Clear cache for specific user} {--all : Clear all user caches by incrementing version}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear role and permission caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->option('user-id');
        $clearAll = $this->option('all');

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error(__('console.user_not_found', ['id' => $userId]));

                return 1;
            }

            $user->clearCache();
            $this->info(__('console.cache_cleared_for_user', ['name' => $user->name, 'id' => $userId]));
        } elseif ($clearAll) {
            $this->clearAllUserCaches();
            $this->info(__('console.all_user_caches_cleared'));
        } else {
            $this->clearGlobalCaches();
        }

        return 0;
    }

    /**
     * Clear all user caches by incrementing version
     */
    private function clearAllUserCaches(): void
    {
        /** @var int $currentVersion */
        $currentVersion = Cache::get('user_cache_version', 1);
        $newVersion = $currentVersion + 1;

        Cache::put('user_cache_version', $newVersion, CacheKeys::CACHE_TTL);

        $this->info(__('console.cache_version_incremented', ['from' => $currentVersion, 'to' => $newVersion]));
        $this->info(__('console.all_user_caches_invalidated'));
    }

    /**
     * Clear global role and permission caches
     */
    private function clearGlobalCaches(): void
    {
        // Clear global caches
        Cache::forget(CacheKeys::ALL_ROLES_CACHE_KEY);
        Cache::forget(CacheKeys::ALL_PERMISSIONS_CACHE_KEY);

        $this->info(__('console.global_caches_cleared'));
    }
}
