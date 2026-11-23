<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'office_address' => Setting::getVal('office_address', 'Av. Bush, Ciudad Universitaria UAGRM, Módulo 236, Santa Cruz de la Sierra, Bolivia'),
                'office_map_embed' => Setting::getVal('office_map_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3799.25585675402!2d-63.19736562426867!3d-17.779644383174545!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x93f1e7e43ad64b97%3A0xd64dfb76813bfab1!2sFacultad%20Integral%20del%20Norte%20UAGRM!5e0!3m2!1ses!2sbo!4v1700000000000'),
                'contact_email' => Setting::getVal('contact_email', 'contacto@feder.edu.bo'),
                'contact_phone' => Setting::getVal('contact_phone', '+591 70000000'),
                'office_lat' => Setting::getVal('office_lat', '-17.779644'),
                'office_lng' => Setting::getVal('office_lng', '-63.195177'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'office_address' => ['required', 'string', 'max:255'],
            'office_map_embed' => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'office_lat' => ['nullable', 'numeric'],
            'office_lng' => ['nullable', 'numeric'],
        ]);

        foreach ($data as $key => $value) {
            Setting::setVal($key, (string) ($value ?? ''));
        }

        return back()->with('success', 'Configuración de la sede institucional guardada correctamente.');
    }
}
