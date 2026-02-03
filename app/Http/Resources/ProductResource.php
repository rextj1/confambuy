<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->categories->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'active' => $this->active,
            'featured' => $this->featured,
            'taxable' => $this->taxable,
            'published_at' => $this->published_at,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,
        ];
    }
}
