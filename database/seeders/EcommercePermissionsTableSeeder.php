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
                'name'        => 'Can View Ecommerce Products',
                'slug'        => 'view.ecommerceproducts',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Ecommerce Products',
                'slug'        => 'create.ecommerceproducts',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Ecommerce Products',
                'slug'        => 'edit.ecommerceproducts',
                'description' => 'Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Ecommerce Products',
                'slug'        => 'delete.ecommerceproducts',
                'description' => 'Manage Ecommerce Product Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Ecommerce Products',
                'slug'        => 'manage.ecommerceproducts',
                'description' => 'Manage Ecommerce Product Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Category',
                'slug'        => 'view.ecommercecategory',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Ecommerce Category',
                'slug'        => 'create.ecommercecategory',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Ecommerce Category',
                'slug'        => 'edit.ecommercecategory',
                'description' => 'Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Ecommerce Category',
                'slug'        => 'delete.ecommercecategory',
                'description' => 'Manage Ecommerce Category Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Ecommerce Category',
                'slug'        => 'manage.ecommercecategory',
                'description' => 'Manage Ecommerce Category Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Subcategory',
                'slug'        => 'view.ecommercesubcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Ecommerce Subcategory',
                'slug'        => 'create.ecommercesubcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Ecommerce Subcategory',
                'slug'        => 'edit.ecommercesubcategory',
                'description' => 'Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Ecommerce Subcategory',
                'slug'        => 'delete.ecommercesubcategory',
                'description' => 'Manage Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Ecommerce Subcategory',
                'slug'        => 'manage.ecommercesubcategory',
                'description' => 'Manage Ecommerce Subcategory Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Customers',
                'slug'        => 'view.ecommercecustomers',
                'description' => 'Ecommerce Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Ecommerce Customers',
                'slug'        => 'create.ecommercecustomers',
                'description' => 'Ecommerce Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Ecommerce Customers',
                'slug'        => 'edit.ecommercecustomers',
                'description' => 'Ecommerce Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Customers',
                'slug'        => 'delete.ecommercecustomers',
                'description' => 'Manage Ecommerce Customers Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Ecommerce Customers',
                'slug'        => 'manage.ecommercecustomers',
                'description' => 'Manage Ecommerce Customers Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Orders',
                'slug'        => 'view.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Ecommerce Orders',
                'slug'        => 'create.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Ecommerce Orders',
                'slug'        => 'edit.ecommerceorders',
                'description' => 'Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Ecommerce Orders',
                'slug'        => 'delete.ecommerceorders',
                'description' => 'Manage Ecommerce Order Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Ecommerce Orders',
                'slug'        => 'manage.ecommerceorders',
                'description' => 'Manage Ecommerce Order Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Vendors',
                'slug'        => 'view.ecommervendors',
                'description' => 'Ecommerce Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Ecommerce Vendors',
                'slug'        => 'create.ecommervendors',
                'description' => 'Ecommerce Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Edit Ecommerce Vendors',
                'slug'        => 'edit.ecommervendors',
                'description' => 'Ecommerce Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Delete Ecommerce Vendors',
                'slug'        => 'delete.ecommervendors',
                'description' => 'Manage Ecommerce Vendor Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage Ecommerce Vendors',
                'slug'        => 'manage.ecommervendors',
                'description' => 'Manage Ecommerce Vendor Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Reports',
                'slug'        => 'view.ecommercereports',
                'description' => 'Ecommerce Report Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Export Ecommerce Reports',
                'slug'        => 'export.ecommercereports',
                'description' => 'Ecommerce Report Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce AuditLogs',
                'slug'        => 'view.ecommerceauditlogs',
                'description' => 'Ecommerce AuditLog Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Export Ecommerce AuditLogs',
                'slug'        => 'manage.ecommerceauditlogs',
                'description' => 'Manage Ecommerce AuditLog Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Complaints',
                'slug'        => 'view.ecommercecomplaints',
                'description' => 'Ecommerce Complaints Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Export Ecommerce Complaints',
                'slug'        => 'manage.ecommercecomplaints',
                'description' => 'Manage Ecommerce Complaints Management',
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
                'description' => 'Manage EcommercePlan Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Manage EcommercePlan',
                'slug'        => 'manage.ecommerceplans',
                'description' => 'Manage EcommercePlan Management',
                'model'       => 'Permission',
            ],

            [
                'name'        => 'Can View Ecommerce Banner',
                'slug'        => 'view.ecommercebanner',
                'description' => 'Ecommerce Banner Management',
                'model'       => 'Permission',
            ],
            [
                'name'        => 'Can Create Ecommerce Banner',
                'slug'        => 'create.ecommercebanner',
                'description' => 'Ecommerce Banner Management',
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
