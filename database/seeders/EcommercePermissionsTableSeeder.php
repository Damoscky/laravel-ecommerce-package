<?php

namespace SbscPackage\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;

class EcommercePermissionsTableSeeder extends Seeder
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
                'name'        => 'Can View Products',
                'slug'        => 'view.products',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Products',
                'slug'        => 'create.products',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Products',
                'slug'        => 'edit.products',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Products',
                'slug'        => 'delete.products',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Products',
                'slug'        => 'delete.products',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Category',
                'slug'        => 'view.category',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Category',
                'slug'        => 'create.category',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Category',
                'slug'        => 'edit.Category',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Category',
                'slug'        => 'delete.category',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Category',
                'slug'        => 'manage.category',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Subcategory',
                'slug'        => 'view.subcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Subcategory',
                'slug'        => 'create.subcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Subcategory',
                'slug'        => 'edit.subcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Subcategory',
                'slug'        => 'delete.subcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Subcategory',
                'slug'        => 'manage.subcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Customers',
                'slug'        => 'view.customers',
                'description' => 'Ecommerce Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Customers',
                'slug'        => 'create.customers',
                'description' => 'Ecommerce Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Customers',
                'slug'        => 'edit.customers',
                'description' => 'Ecommerce Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Customers',
                'slug'        => 'delete.customers',
                'description' => 'Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Customers',
                'slug'        => 'manage.customers',
                'description' => 'Customers Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Orders',
                'slug'        => 'view.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Orders',
                'slug'        => 'create.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Orders',
                'slug'        => 'edit.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Orders',
                'slug'        => 'delete.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Orders',
                'slug'        => 'manage.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Vendors',
                'slug'        => 'view.vendors',
                'description' => 'Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Vendors',
                'slug'        => 'create.vendors',
                'description' => 'Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Vendors',
                'slug'        => 'edit.vendors',
                'description' => 'Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Vendors',
                'slug'        => 'delete.vendors',
                'description' => 'Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Vendors',
                'slug'        => 'manage.vendors',
                'description' => 'Vendor Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Reports',
                'slug'        => 'view.reports',
                'description' => 'Report Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Export Reports',
                'slug'        => 'export.reports',
                'description' => 'Report Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View AuditLogs',
                'slug'        => 'view.auditlogs',
                'description' => 'AuditLog Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Export AuditLogs',
                'slug'        => 'manage.auditlogs',
                'description' => 'AuditLog Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Complaints',
                'slug'        => 'view.complaints',
                'description' => 'Complaints Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Export Complaints',
                'slug'        => 'manage.complaints',
                'description' => 'Complaints Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View EcommercePlan',
                'slug'        => 'view.ecommerceplans',
                'description' => 'EcommercePlan Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create EcommercePlan',
                'slug'        => 'create.ecommerceplans',
                'description' => 'EcommercePlan Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit EcommercePlan',
                'slug'        => 'edit.ecommerceplans',
                'description' => 'EcommercePlan Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete EcommercePlan',
                'slug'        => 'delete.ecommerceplans',
                'description' => 'EcommercePlan Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage EcommercePlan',
                'slug'        => 'manage.ecommerceplans',
                'description' => 'EcommercePlan Management',
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
