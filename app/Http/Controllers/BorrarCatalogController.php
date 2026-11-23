<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BorrarCatalogController extends Controller
{
    /**
     * Muestra el entorno de pruebas aislado (sandbox) en BORRAR
     * aplicando la paleta corporativa Naranja (#F7780F), Verde (#13AC44) y Plomo (#9F9FA1 / #1E252B).
     */
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', ''));
        $categorySlug = $request->input('category', '');
        $level = $request->input('level', '');
        $variant = $request->input('variant', 'impacto'); // impacto | ejecutivo | minimalista

        $query = Course::with(['category:id,name,slug,icon', 'instructor:id,name,avatar'])
            ->withCount('enrollments')
            ->where('status', 'published');

        if ($search) {
            $query->where(function ($b) use ($search) {
                $b->where('title', 'ilike', "%{$search}%")
                  ->orWhere('hook', 'ilike', "%{$search}%");
            });
        }

        if ($categorySlug) {
            $query->whereHas('category', function ($b) use ($categorySlug) {
                $b->where('slug', $categorySlug);
            });
        }

        if ($level) {
            $query->where('level', $level);
        }

        $courses = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        // Asignación explícita de colores corporativos Naranja / Verde / Plomo eliminando cualquier morado residual
        $palette = [
            '#F7780F', // Naranja Oficial Feder
            '#13AC44', // Verde Oficial Feder
            '#1E252B', // Plomo Pizarra Profundo
            '#EA580C', // Naranja Intenso
            '#0F8B37', // Verde Esmeralda
            '#64748B', // Plomo Medio Metal
        ];

        $demoImages = [
            'assets/img/demo/course1.png',
            'assets/img/demo/course2.png',
            'assets/img/demo/course3.png',
        ];

        $courses->getCollection()->transform(function ($course, $index) use ($palette, $demoImages) {
            // Asignación de colores corporativos
            $course->color = $palette[$index % count($palette)];
            // Asignamos imágenes fotográficas reales de prueba para verificar que los colores no dependan del fondo
            if (empty($course->cover_image)) {
                $course->cover_image = $demoImages[$index % count($demoImages)];
            }
            return $course;
        });

        $categories = Category::orderBy('name')->get(['id', 'name', 'slug', 'icon']);

        return Inertia::render('Borrar/Catalog', [
            'courses' => $courses,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'level' => $level,
                'variant' => $variant,
            ],
        ]);
    }
}
