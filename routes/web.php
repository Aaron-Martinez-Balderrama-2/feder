<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Entry Route (React + Vite)
|--------------------------------------------------------------------------
| Entrega el contenedor HTML de la SPA de React para todas las rutas de
| cliente, permitiendo navegación limpia tanto en raíz local como en
| subdirectorios institucionales (/inf513/grupo30sa/proyecto2).
*/

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|up|build|uploads|assets).*$')->name('spa');
