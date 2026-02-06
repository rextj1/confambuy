<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CleanupOrphanMedia implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Media::query()
            ->whereIn('model_type', [Product::class, ProductSku::class])
            ->whereDoesntHaveMorph('model', [Product::class, ProductSku::class])
            ->orderBy('id')
            ->chunkById(200, function ($mediaItems): void {
                foreach ($mediaItems as $media) {
                    $media->delete();
                }
            });
    }
}
