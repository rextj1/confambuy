<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics' => ['Smartphones', 'Laptops', 'Audio', 'Cameras'],
            'Fashion' => ['Men', 'Women', 'Kids', 'Accessories'],
            'Home & Garden' => ['Furniture', 'Decor', 'Kitchen', 'Lighting'],
            'Sports' => ['Gym Equipment', 'Cycling', 'Running', 'Team Sports'],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'description' => fake()->sentence(),
                'is_active' => true,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'slug' => Str::slug($childName),
                    'description' => fake()->sentence(),
                    'is_active' => true,
                ]);
            }
        }
    }
}