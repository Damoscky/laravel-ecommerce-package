<?php

namespace SbscPackage\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use SbscPackage\Ecommerce\Interfaces\UserStatusInterface;
use App\Models\User;
use SbscPackage\Ecommerce\Models\Category;

class EcommerceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $categories = [
            [
                'slug' => 'electronics',
                'name' => 'Electronics',
                'short_description' => '',
                'file_path' => '',
                'created_by' => 1,
                'is_active' => true,
                'status' => 'Approved'
            ],
            
        ];


        /*
         * Add Categories Items
         *
         */
        foreach ($categories as $category) {
            $newcategory = Category::where('slug', '=', $category['slug'])->first();
            if ($newcategory === null) {
                $createnewcategory = Category::create([
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'short_description' => $category['short_description'],
                    'file_path' => $category['file_path'],
                    'created_by' => $category['created_by'],
                    'is_active' => $category['is_active'],
                    'status'   => $category['status'],
                ]);
            }
        }

    }
}
