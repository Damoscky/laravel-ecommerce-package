<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*
         * Permission Types
         *
         */
        $Permissionitems = [
            [
                'name'        => 'Can View Users',
                'slug'        => 'view.users',
                'description' => 'User Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Users',
                'slug'        => 'create.users',
                'description' => 'User Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Users',
                'slug'        => 'edit.users',
                'description' => 'User Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Users',
                'slug'        => 'delete.users',
                'description' => 'User Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can Access Category',
                'slug'        => 'access.category',
                'description' => 'Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Category',
                'slug'        => 'manage.category',
                'description' => 'Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Product',
                'slug'        => 'create.product',
                'description' => 'Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Product',
                'slug'        => 'delete.product',
                'description' => 'Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Product',
                'slug'        => 'edit.product',
                'description' => 'Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can view Product',
                'slug'        => 'view.product',
                'description' => 'Product Management',
                'model'       => 'Permission',
            ],
        ];

        /*
         * Add Permission Items
         *
         */
        foreach ($Permissionitems as $Permissionitem) {
            $newPermissionitem = config('roles.models.permission')::where('slug', '=', $Permissionitem['slug'])->first();
            if ($newPermissionitem === null) {
                $newPermissionitem = config('roles.models.permission')::create([
                    'name'          => $Permissionitem['name'],
                    'slug'          => $Permissionitem['slug'],
                    'description'   => $Permissionitem['description'],
                    'model'         => $Permissionitem['model'],
                ]);
            }
        }
    }
}
