<?php

namespace SbscPackage\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use SbscPackage\Ecommerce\Interfaces\UserStatusInterface;
use App\Models\User;



class EcommerceRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $RoleItems = [
            [
                'slug' => 'ecommerceadmin',
                'name' => 'Ecommerce Admin',
                'description' => 'Ecommerce Admin Role',
                'level' => 10
            ],
            [
                'slug' => 'ecommercesuperadmin',
                'name' => 'Ecommerce Super Admin',
                'description' => 'Ecommerce Super Admin Role',
                'level' => 11
            ],
            [
                'slug' => 'ecommercecustomer',
                'name' => 'Ecommerce Customer',
                'description' => 'Ecommerce Customer Role',
                'level' => 12
            ],
            [
                'slug' => 'ecommercevendor',
                'name' => 'Ecommerce Vendor',
                'description' => 'Ecommerce Vendor Role',
                'level' => 12
            ],
        ];


/*
         * Add Role Items
         *
         */
        foreach ($RoleItems as $RoleItem) {
            $newRoleItem = config('roles.models.role')::where('slug', '=', $RoleItem['slug'])->first();
            if ($newRoleItem === null) {
                $newRoleItem = config('roles.models.role')::create([
                    'name'          => $RoleItem['name'],
                    'slug'          => $RoleItem['slug'],
                    'description'   => $RoleItem['description'],
                    'level'         => $RoleItem['level'],
                ]);
            }
        }

        $ecommerceAdminRole = config('roles.models.role')::where('name', '=', 'Ecommerce Admin')->first();
        $ecommerceSuperAdminRole = config('roles.models.role')::where('name', '=', 'Ecommerce Super Admin')->first();
        $ecommerceCustomerRole = config('roles.models.role')::where('name', '=', 'Ecommerce Customer')->first();
        $permissions = config('roles.models.permission')::all();

        if (User::where('email', '=', 'salesmanager@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Sales-Manager',
                'lastname'     => 'Fanerp',
                'email'    => 'salesmanager@fanerp.com',
                'phoneno' => '09088229593',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($salesManagerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }
    



        if (User::where('email', '=', 'salesofficer@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Sales-Officer',
                'lastname'     => 'Fanerp',
                'email'    => 'salesofficer@fanerp.com',
                'phoneno' => '09058229693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($salesOfficerRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }



        if (User::where('email', '=', 'crmadmin@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'CRM Admin',
                'lastname'     => 'Fanerp',
                'email'    => 'crmadmin@fanerp.com',
                'phoneno' => '09088209693',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($crmAdminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }




    }
}
