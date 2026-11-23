<?php

namespace Tests\Feature;

use Tests\TestCase;

class BorrarSandboxTest extends TestCase
{
    public function test_borrar_directory_exists_locally_with_sandbox_files(): void
    {
        $baseDir = base_path('BORRAR');
        $this->assertDirectoryExists($baseDir);

        $this->assertFileExists($baseDir . DIRECTORY_SEPARATOR . 'Catalog.vue');
        $this->assertFileExists($baseDir . DIRECTORY_SEPARATOR . 'ThemeTokens.css');
        $this->assertFileExists($baseDir . DIRECTORY_SEPARATOR . 'BorrarCatalogController.php');
        $this->assertFileExists($baseDir . DIRECTORY_SEPARATOR . 'README.md');

        // Verify official hex codes extracted from favicon.png
        $tokens = file_get_contents($baseDir . DIRECTORY_SEPARATOR . 'ThemeTokens.css');
        $this->assertStringContainsString('#F7780F', $tokens); // Naranja
        $this->assertStringContainsString('#13AC44', $tokens); // Verde
        $this->assertStringContainsString('#1E252B', $tokens); // Plomo Pizarra
        $this->assertStringContainsString('#9F9FA1', $tokens); // Plomo Engranaje
    }

    public function test_borrar_route_renders_successfully(): void
    {
        $response = $this->get('/borrar');
        $response->assertStatus(200);
    }
}
