<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'Color' => ['Red', 'Blue', 'Green', 'Black', 'White'],
            'Size' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
            'Material' => ['Cotton', 'Polyester', 'Leather', 'Wool'],
            'Memory' => ['64GB', '128GB', '256GB', '512GB'],
        ];

        foreach ($attributes as $name => $values) {
            $attribute = Attribute::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'type' => 'select',
                ]
            );

            foreach ($values as $value) {
                $attribute->values()->updateOrCreate(
                    ['value' => $value],
                    ['label' => $value]
                );
            }
        }
    }
}
