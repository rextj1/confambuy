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
            $attribute = Attribute::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => 'select',
            ]);

            foreach ($values as $value) {
                $attribute->values()->create([
                    'value' => $value,
                    'label' => $value,
                ]);
            }
        }
    }
}