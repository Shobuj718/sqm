<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default roles
        $adminRole = \App\Models\Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator']
        );
        $managerRole = \App\Models\Role::firstOrCreate(
            ['name' => 'manager'],
            ['description' => 'Manager']
        );
        $userRole = \App\Models\Role::firstOrCreate(
            ['name' => 'user'],
            ['description' => 'User']
        );

        // Create default permissions
        $permissions = [
            'view-pages' => 'View pages',
            'view-subscription' => 'View subscription',
            'create-subscription' => 'Create subscription',
            'manage-facebook' => 'Manage Facebook',
        ];

        $permissionModels = [];
        foreach ($permissions as $name => $description) {
            $permissionModels[$name] = \App\Models\Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Assign permissions to roles
        $adminRole->permissions()->syncWithoutDetaching(array_values($permissionModels));
        $managerRole->permissions()->syncWithoutDetaching([
            $permissionModels['view-pages'],
            $permissionModels['view-subscription'],
            $permissionModels['create-subscription'],
            $permissionModels['manage-facebook'],
        ]);
        $userRole->permissions()->syncWithoutDetaching([
            $permissionModels['view-pages'],
            $permissionModels['view-subscription'],
        ]);

        // Create test user
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => User::ROLE_USER,
        ]);

        // Assign user role
        $testUser->assignRole($userRole);
    }
}
