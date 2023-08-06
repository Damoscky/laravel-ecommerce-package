<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Interfaces\UserStatusInterface;
use Illuminate\Support\Str;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userRole = config('roles.models.role')::where('name', '=', 'Customer')->first();
        $developerRole = config('roles.models.role')::where('name', '=', 'Developer')->first();
        $adminRole = config('roles.models.role')::where('name', '=', 'Admin')->first();
        $superAdminRole = config('roles.models.role')::where('name', '=', 'Super Admin')->first();
        $staffRole = config('roles.models.role')::where('name', '=', 'Staff')->first();
        $cashierRole = config('roles.models.role')::where('name', '=', 'Cashier')->first();
        $financeRole = config('roles.models.role')::where('name', '=', 'Finance Manager')->first();
        $employeeRole = config('roles.models.role')::where('name', '=', 'Employee')->first();
        $hrManagerRole = config('roles.models.role')::where('name', '=', 'HR Manager')->first();
        $storeManagerRole = config('roles.models.role')::where('name', '=', 'Store Manager')->first();
        $warehouseManagerRole = config('roles.models.role')::where('name', '=', 'Warehouse Manager')->first();
        $lineManagerRole = config('roles.models.role')::where('name', '=', 'Line Manager')->first();
        $procurementRequestorRole = config('roles.models.role')::where('name', '=', 'Procurement Requestor')->first();
        $procurementOfficerRole = config('roles.models.role')::where('name', '=', 'Procurement Officer')->first();
        $permissions = config('roles.models.permission')::all();

        /*
         * Add Users
         *
         */
        if (User::where('email', '=', 'admin@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Fanerp',
                'lastname'     => 'Admin',
                'email'    => 'admin@fanerp.com',
                'phoneno' => '09088449933',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($adminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'cashier@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Fanerp',
                'lastname'     => 'Cashier',
                'email'    => 'cashier@fanerp.com',
                'phoneno' => '09088449933',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($cashierRole);

            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'superadmin@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Fanerp',
                'lastname'     => 'Super Admin',
                'email'    => 'superadmin@fanerp.com',
                'phoneno' => '08099884422',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($superAdminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'developer@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Developer',
                'lastname'     => 'SBSC',
                'email'    => 'developer@fanerp.com',
                'phoneno' => '09051449933',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);
            // $2y$10$AFt.nBOXTwXbulUdpSIfdOXY8EZrDh/DSCsNqX1a.pjaM58NG.UBO

            $newUser->attachRole($developerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'user@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'User',
                'lastname'     => 'Fanerp',
                'email'    => 'user@fanerp.com',
                'phoneno' => '09088449693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($userRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'finance@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Finance',
                'lastname'     => 'Fanerp',
                'email'    => 'finance@fanerp.com',
                'phoneno' => '09088449695',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($financeRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'hr@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'HR',
                'lastname'     => 'Fanerp',
                'email'    => 'hr@fanerp.com',
                'phoneno' => '09088229693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($hrManagerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }

        }

        if (User::where('email', '=', 'linemanager@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Line-Manager',
                'lastname'     => 'Fanerp',
                'email'    => 'linemanager@fanerp.com',
                'phoneno' => '09088229693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($lineManagerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'storemanager@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Store Manager',
                'lastname'     => 'Fanerp',
                'email'    => 'storemanager@fanerp.com',
                'phoneno' => '09088229693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($storeManagerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'warehousemanager@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Warehouse Manager',
                'lastname'     => 'Fanerp',
                'email'    => 'warehousemanager@fanerp.com',
                'phoneno' => '09088209693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($warehouseManagerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'procurementrequestor@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'ProcurementRequestor',
                'lastname'     => 'Fanerp',
                'email'    => 'procurementrequestor@fanerp.com',
                'phoneno' => '09088209693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($procurementRequestorRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'procurementofficer@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'ProcurementOfficer',
                'lastname'     => 'Fanerp',
                'email'    => 'procurementofficer@fanerp.com',
                'phoneno' => '09088209693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($procurementOfficerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }
    }
}
