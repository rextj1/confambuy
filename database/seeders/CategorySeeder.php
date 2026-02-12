<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $parentCount = 10;
        $childrenPerParent = 5;

        for ($parentIndex = 1; $parentIndex <= $parentCount; $parentIndex++) {
            $parentName = "Category {$parentIndex}";
            $parentSlug = Str::slug($parentName);

            $parent = Category::query()->updateOrCreate(
                ['slug' => $parentSlug],
                [
                    'name' => $parentName,
                    'description' => "{$parentName} description",
                    'is_active' => true,
                    'sort_order' => $parentIndex,
                    'parent_id' => null,
                ]
            );

            for ($childIndex = 1; $childIndex <= $childrenPerParent; $childIndex++) {
                $childName = "{$parentName} - Child {$childIndex}";
                $childSlug = Str::slug($childName);

                Category::query()->updateOrCreate(
                    ['slug' => $childSlug],
                    [
                        'name' => $childName,
                        'description' => "{$childName} description",
                        'is_active' => true,
                        'sort_order' => $childIndex,
                        'parent_id' => $parent->id,
                    ]
                );
            }
        }
    }
}
