<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scribe_documentation_can_be_generated_and_served(): void
    {
        $exitCode = Artisan::call('scribe:generate', [
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists(resource_path('views/scribe/index.blade.php'));
        $this->assertFileExists(Storage::disk('local')->path('scribe/openapi.yaml'));
        $this->assertFileExists(Storage::disk('local')->path('scribe/collection.json'));

        $this->get('/docs')->assertOk();
        $this->get('/docs.openapi')->assertOk();
        $this->getJson('/docs.postman')
            ->assertOk()
            ->assertJsonStructure(['info', 'item']);
    }
}
