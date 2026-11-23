<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', $request->input('q', '')));
        $categorySlug = $request->input('category');
        $level = $request->input('level');

        $cacheKey = 'catalog_page_' . md5(serialize($request->all()));

        $courses = Cache::remember($cacheKey, 60, function () use ($search, $categorySlug, $level) {
            $query = Course::with(['category:id,name,slug,icon', 'instructor:id,name,avatar'])
                ->withCount('enrollments')
                ->where('status', 'published');

            if ($search) {
                $query->where(function ($b) use ($search) {
                    $b->where('title', 'ilike', "%{$search}%")
                      ->orWhere('hook', 'ilike', "%{$search}%")
                      ->orWhere('description', 'ilike', "%{$search}%");
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

            return $query->orderBy('id', 'desc')->paginate(10)->withQueryString();
        });

        $categories = Cache::remember('catalog_categories', 300, function () {
            return Category::orderBy('name')->get(['id', 'name', 'slug', 'icon']);
        });

        return Inertia::render('Catalog/Index', [
            'courses' => $courses,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'level' => $level,
            ],
        ]);
    }

    public function __invoke(Request $request): Response
    {
        return $this->index($request);
    }
}
