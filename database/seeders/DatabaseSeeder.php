<?php

namespace SbscPackage\Ecommerce\Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use SbscPackage\Ecommerce\Database\Seeders\EcommerceRoleSeeder;
use SbscPackage\Ecommerce\Database\Seeders\EcommercePermissionsTableSeeder;
use SbscPackage\Ecommerce\Database\Seeders\EcommerceCategorySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EcommercePermissionsTableSeeder::class,
            EcommerceRoleSeeder::class,
            EcommerceCategorySeeder::class,
        ]);
    }
}
