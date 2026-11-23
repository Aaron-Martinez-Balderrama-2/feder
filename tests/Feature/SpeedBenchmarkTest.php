<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpeedBenchmarkTest extends TestCase
{
    public function test_home_page_speed(): void
    {
        // First request (warms up connection & cache)
        $start1 = microtime(true);
        $res1 = $this->get('/');
        $time1 = (microtime(true) - $start1) * 1000;
        $res1->assertStatus(200);

        // Second request (subsequent user click)
        $start2 = microtime(true);
        $res2 = $this->get('/');
        $time2 = (microtime(true) - $start2) * 1000;
        $res2->assertStatus(200);

        echo "\n[BENCHMARK] Home Page ('/'):\n";
        echo "  - Initial Request: " . round($time1, 2) . " ms\n";
        echo "  - Subsequent Request: " . round($time2, 2) . " ms\n";
    }

    public function test_catalog_page_speed(): void
    {
        $start1 = microtime(true);
        $res1 = $this->get('/cursos');
        $time1 = (microtime(true) - $start1) * 1000;
        $res1->assertStatus(200);

        $start2 = microtime(true);
        $res2 = $this->get('/cursos');
        $time2 = (microtime(true) - $start2) * 1000;
        $res2->assertStatus(200);

        echo "[BENCHMARK] Catalog Page ('/cursos'):\n";
        echo "  - Initial Request: " . round($time1, 2) . " ms\n";
        echo "  - Subsequent Request: " . round($time2, 2) . " ms\n";
    }

    public function test_sitemap_page_speed(): void
    {
        $start1 = microtime(true);
        $res1 = $this->get('/mapa-del-sitio');
        $time1 = (microtime(true) - $start1) * 1000;
        $res1->assertStatus(200);

        $start2 = microtime(true);
        $res2 = $this->get('/mapa-del-sitio');
        $time2 = (microtime(true) - $start2) * 1000;
        $res2->assertStatus(200);

        echo "[BENCHMARK] Sitemap Page ('/mapa-del-sitio'):\n";
        echo "  - Initial Request: " . round($time1, 2) . " ms\n";
        echo "  - Subsequent Request: " . round($time2, 2) . " ms\n";
    }
}
