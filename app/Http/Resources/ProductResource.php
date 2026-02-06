<?php

namespace App\Http\Resources;

use App\Actions\Pricing\CalculateSellingPrice;
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
        $pricing = app(CalculateSellingPrice::class)->forProduct($this->resource);

        return [
            'currency' => (string) config('pricing.currency', 'NGN'),
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'selling_price' => $pricing['selling_price'],
            'discount_amount' => $pricing['discount_amount'],
            'discount_percent' => $pricing['discount_percent'],
            'active' => $this->active,
            'featured' => $this->featured,
            'taxable' => $this->taxable,
            'published_at' => $this->published_at,
            'images' => $this->getMedia('images')
                ->map(function ($media): array {
                    return [
                        'id' => $media->id,
                        'url' => $media->getFullUrl(),
                        'thumb_url' => $media->getFullUrl('thumb'),
                        'alt' => data_get($media->custom_properties, 'alt'),
                        'position' => $media->order_column,
                        'is_featured' => (bool) data_get($media->custom_properties, 'is_featured', false),
                        'product_sku_id' => data_get($media->custom_properties, 'product_sku_id'),
                    ];
                })
                ->values(),
            'skus' => $this->whenLoaded('skus', function () {
                return $this->skus->map(function ($sku): array {
                    return [
                        'id' => $sku->id,
                        'sku' => $sku->sku,
                        'title' => $sku->title,
                        'price' => $sku->price,
                        'images' => $sku->getMedia('images')
                            ->map(function ($media): array {
                                return [
                                    'id' => $media->id,
                                    'url' => $media->getFullUrl(),
                                    'thumb_url' => $media->getFullUrl('thumb'),
                                    'alt' => data_get($media->custom_properties, 'alt'),
                                    'position' => $media->order_column,
                                    'is_featured' => (bool) data_get($media->custom_properties, 'is_featured', false),
                                ];
                            })
                            ->values(),
                    ];
                })->values();
            }),
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,
        ];
    }
}
