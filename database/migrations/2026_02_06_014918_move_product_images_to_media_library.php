<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('product_images') || ! Schema::hasTable('media')) {
            return;
        }

        $disk = (string) config('media-library.disk_name', 'public');

        DB::table('product_images')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(200, function ($images) use ($disk): void {
                foreach ($images as $image) {
                    $path = (string) $image->path;
                    $fileName = basename($path);
                    $name = pathinfo($fileName, PATHINFO_FILENAME) ?: 'image';

                    DB::table('media')->insert([
                        'model_type' => \App\Models\Product::class,
                        'model_id' => $image->product_id,
                        'collection_name' => 'images',
                        'name' => $name,
                        'file_name' => $fileName,
                        'mime_type' => null,
                        'disk' => $disk,
                        'conversions_disk' => null,
                        'size' => 0,
                        'manipulations' => json_encode([]),
                        'custom_properties' => json_encode([
                            'alt' => $image->alt,
                            'is_featured' => (bool) $image->is_featured,
                            'product_sku_id' => $image->product_sku_id,
                            'legacy_path' => $path,
                        ]),
                        'generated_conversions' => json_encode([]),
                        'responsive_images' => json_encode([]),
                        'order_column' => $image->position,
                        'created_at' => $image->created_at ?? now(),
                        'updated_at' => $image->updated_at ?? now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        DB::table('media')
            ->where('model_type', \App\Models\Product::class)
            ->where('collection_name', 'images')
            ->delete();
    }
};
