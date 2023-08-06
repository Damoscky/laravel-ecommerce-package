<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*
         * Role Types
         *
         */
        $RoleItems = [
            [
                'slug' => 'customer',
                'name' => 'Customer',
                'description' => 'Customer Role',
                'level' => 1
            ],
            [
                'slug' => 'admin',
                'name' => 'Admin',
                'description' => 'Admin Role',
                'level' => 2
            ],
            [
                'slug' => 'superadmin',
                'name' => 'Super Admin',
                'description' => 'Super Admin Role',
                'level' => 3
            ],
            [
                'slug' => 'developer',
                'name' => 'Developer',
                'description' => 'Developer Role',
                'level' => 4
            ],
            [
                'slug' => 'staff',
                'name' => 'Staff',
                'description' => 'Staff Role',
                'level' => 5
            ],
            [
                'slug' => 'cashier',
                'name' => 'Cashier',
                'description' => 'Cashier Role',
                'level' => 5
            ],
            [
                'slug' => 'finance',
                'name' => 'Finance Manager',
                'description' => 'Finance Role',
                'level' => 5
            ],
            [
                'slug' => 'employee',
                'name' => 'Employee',
                'description' => 'Employee Role',
                'level' => 6
            ],
            [
                'slug' => 'linemanager',
                'name' => 'Line Manager',
                'description' => 'Line Manager Role',
                'level' => 7
            ],
            [
                'slug' => 'storemanager',
                'name' => 'Store Manager',
                'description' => 'Store Manager Role',
                'level' => 7
            ],
            [
                'slug' => 'warehousemanager',
                'name' => 'Warehouse Manager',
                'description' => 'Warehouse Manager Role',
                'level' => 7
            ],
            [
                'slug' => 'hrmanager',
                'name' => 'HR Manager',
                'description' => 'HR Manager',
                'level' => 8
            ],
            [
                'slug' => 'procurementrequestor',
                'name' => 'Procurement Requestor',
                'description' => 'Procurement Requestor',
                'level' => 9
            ],
            [
                'slug' => 'procurementofficer',
                'name' => 'Procurement Officer',
                'description' => 'Procurement Officer',
                'level' => 10
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
    }
}
