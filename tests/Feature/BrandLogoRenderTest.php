<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandLogoRenderTest extends TestCase
{
    public function test_favicon_png_exists_and_is_valid(): void
    {
        $path = public_path('favicon.png');
        $this->assertFileExists($path);
        $this->assertGreaterThan(100000, filesize($path));

        // Also verify in assets/img/favicon.png
        $assetPath = public_path('assets/img/favicon.png');
        $this->assertFileExists($assetPath);
    }

    public function test_html_head_includes_favicon_png(): void
    {
        $response = $this->get('/cursos');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('<link rel="icon" href="/favicon.png" type="image/png">', $content);
    }

    public function test_compiled_bundle_contains_favicon_png(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);

        // Find AppLayout chunk
        $layoutChunk = null;
        foreach ($manifest as $key => $entry) {
            if (str_ends_with($entry['file'], '.js') && (str_contains($key, 'AppLayout') || (isset($entry['name']) && $entry['name'] === 'AppLayout'))) {
                $layoutChunk = public_path('build/' . $entry['file']);
                break;
            }
        }

        $this->assertNotNull($layoutChunk);
        $this->assertFileExists($layoutChunk);

        $jsContent = file_get_contents($layoutChunk);
        $this->assertStringContainsString('favicon.png', $jsContent);
    }
}
