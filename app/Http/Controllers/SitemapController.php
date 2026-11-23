<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $settings = Cache::remember('sitemap_settings', 3600, function () {
            return [
                'officeAddress' => Setting::getVal('office_address', 'Av. Bush, Ciudad Universitaria UAGRM, Módulo 236, Santa Cruz de la Sierra, Bolivia'),
                'officeMapCoords' => Setting::getVal('office_map_embed', '-17.779644,-63.195177'),
                'officeLat' => (float) Setting::getVal('office_lat', -17.779644),
                'officeLng' => (float) Setting::getVal('office_lng', -63.195177),
            ];
        });

        return Inertia::render('Sitemap', $settings);
    }

    public function __invoke(): Response
    {
        return $this->index();
    }
}
