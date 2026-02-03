<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $parentCount = 10;
        $childrenPerParent = 5;

        $parents = Category::factory()
            ->count($parentCount)
            ->create(['parent_id' => null]);

        foreach ($parents as $parent) {
            Category::factory()
                ->count($childrenPerParent)
                ->create([
                    'parent_id' => $parent->id,
                    'is_active' => true,
                ]);
        }
    }
}
