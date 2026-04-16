<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Console\Command;

class SetupRolesPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:roles-permissions';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Setup roles and permissions in the database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Setting up roles and permissions...');

        // Create roles
        $roles = [
            'admin' => 'Administrator with full access',
            'manager' => 'Manager with limited access',
            'user' => 'Regular user with basic access',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(['name' => $name], ['description' => $description]);
            $this->line("✓ Role '{$name}' created");
        }

        // Create permissions
        $permissions = [
            'view-pages' => 'View Facebook pages',
            'view-subscription' => 'View subscription',
            'create-subscription' => 'Create subscription',
            'manage-facebook' => 'Manage Facebook settings',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], ['description' => $description]);
            $this->line("✓ Permission '{$name}' created");
        }

        // Assign permissions to roles
        $admin = Role::where('name', 'admin')->first();
        $manager = Role::where('name', 'manager')->first();
        $userRole = Role::where('name', 'user')->first();

        $allPermissions = Permission::all();

        $admin->permissions()->syncWithoutDetaching($allPermissions);
        $this->line('✓ Assigned all permissions to Admin role');

        $managerPerms = Permission::whereIn('name', [
            'view-pages',
            'view-subscription',
            'create-subscription',
            'manage-facebook',
        ])->get();
        $manager->permissions()->syncWithoutDetaching($managerPerms);
        $this->line('✓ Assigned permissions to Manager role');

        $userPerms = Permission::whereIn('name', [
            'view-pages',
            'view-subscription',
        ])->get();
        $userRole->permissions()->syncWithoutDetaching($userPerms);
        $this->line('✓ Assigned permissions to User role');

        $this->info('✅ Roles and permissions setup completed!');
    }
}
