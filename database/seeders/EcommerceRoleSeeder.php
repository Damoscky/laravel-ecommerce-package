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
        $ecommerceVendorRole = config('roles.models.role')::where('name', '=', 'Ecommerce Vendor')->first();
        $permissions = config('roles.models.permission')::all();

        if (User::where('email', '=', 'ecommerceadmin@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Ecommerce-Admin',
                'lastname'     => 'Fanerp',
                'email'    => 'ecommerceadmin@fanerp.com',
                'phoneno' => '09088112266',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($ecommerceAdminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'ecommercesuperadmin@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Ecommerce-Super-Admin',
                'lastname'     => 'Fanerp',
                'email'    => 'ecommercesuperadmin@fanerp.com',
                'phoneno' => '08166441994',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($ecommerceSuperAdminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }



        if (User::where('email', '=', 'ecommercevendor@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Ecommerce Vendor',
                'lastname'     => 'Fanerp',
                'email'    => 'ecommercevendor@fanerp.com',
                'phoneno' => '08192761034',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($ecommerceVendorRole);
            
        }

        if (User::where('email', '=', 'ecommercecustomer@fanerp.com')->first() === null) {
            $newUser = User::create([
                'firstname'     => 'Ecommerce Customer',
                'lastname'     => 'Fanerp',
                'email'    => 'ecommercecustomer@fanerp.com',
                'phoneno' => '09188374455',
                'is_verified' => true,
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => true,
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($ecommerceCustomerRole);
            
        }




    }
}
