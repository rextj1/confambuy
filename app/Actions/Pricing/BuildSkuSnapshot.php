<?php

namespace App\Actions\Pricing;

use App\Models\ProductSku;

class BuildSkuSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public function fromSku(ProductSku $sku): array
    {
        $product = $sku->product;

        return [
            'product_id' => $sku->product_id,
            'product_name' => $product?->name,
            'sku_id' => $sku->id,
            'sku' => $sku->sku,
            'title' => $sku->title,
            'price' => (string) $sku->price,
            'cost' => (string) $sku->cost,
            'weight' => (string) $sku->weight,
            'dimensions' => [
                'length' => (string) $sku->length,
                'width' => (string) $sku->width,
                'height' => (string) $sku->height,
            ],
            'attributes' => $sku->attributes,
        ];
    }
}
