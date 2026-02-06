<?php

namespace Tests\Feature;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_resource_returns_absolute_media_urls(): void
    {
        config(['media-library.disk_name' => 'public']);
        config(['filesystems.disks.public.url' => 'http://localhost/storage']);

        Storage::fake('public');

        $product = Product::factory()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $product->addMedia($file)->toMediaCollection('images');

        $sku = ProductSku::factory()->create(['product_id' => $product->id]);
        $skuFile = UploadedFile::fake()->image('sku-photo.jpg');
        $sku->addMedia($skuFile)->toMediaCollection('images');

        $resource = (new ProductResource($product->load('media', 'skus.media')))->resolve();

        $this->assertNotEmpty($resource['images']);
        $this->assertMatchesRegularExpression('#^https?://#', $resource['images'][0]['url']);
        $this->assertMatchesRegularExpression('#^https?://#', $resource['images'][0]['thumb_url']);
        $this->assertNotEmpty($resource['skus']);
        $this->assertMatchesRegularExpression('#^https?://#', $resource['skus'][0]['images'][0]['url']);
        $this->assertMatchesRegularExpression('#^https?://#', $resource['skus'][0]['images'][0]['thumb_url']);
    }
}
