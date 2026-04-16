<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;

class GrantPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:grant-permission {email} {permission}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Grant a permission directly to a user';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $email = $this->argument('email');
        $permissionName = $this->argument('permission');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email '{$email}' not found");
            return;
        }

        $permission = Permission::where('name', $permissionName)->first();
        if (!$permission) {
            $this->error("Permission '{$permissionName}' not found");
            return;
        }

        $user->grantPermission($permission);
        $this->info("✓ Permission '{$permissionName}' granted to {$email}");
    }
}
