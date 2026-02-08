<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'manage services',
            'view services',
            'manage appointments', // Admin: assign, change status
            'view all appointments',
            'view assigned appointments', // User (provider)
            'request appointment', // Client
            'view own appointments', // Client
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission);
        }

        // create roles and assign created permissions

        // Admin
        $role = \Spatie\Permission\Models\Role::findOrCreate('admin');
        $role->givePermissionTo( \Spatie\Permission\Models\Permission::all() );

        // User (Provider/Employee)
        $role = \Spatie\Permission\Models\Role::findOrCreate('user');
        $role->givePermissionTo(['view assigned appointments', 'view services']);

        // Client
        $role = \Spatie\Permission\Models\Role::findOrCreate('client');
        $role->givePermissionTo(['request appointment', 'view own appointments', 'view services']);

        // Super Admin (Global)
        $role = \Spatie\Permission\Models\Role::findOrCreate('super_admin');
        // Give all permissions or specific manage_companies permission
        $role->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        // Create Default Admin User
        // Create Default Admin User
        // Create Default Admin User (Company Admin placeholder, though technically needs company_id)
        // For now, let's create a Super Admin
        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
            ]
        );
        $user->assignRole('super_admin');
    }
}
