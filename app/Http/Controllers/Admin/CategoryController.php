<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    // 50+ curated SVG icon names supported by AppIcon.vue
    public const AVAILABLE_ICONS = [
        'spark', 'target', 'compass', 'brain', 'coin', 'rocket', 'chart', 'shield',
        'code', 'briefcase', 'lightbulb', 'cpu', 'database', 'globe', 'layers',
        'terminal', 'book', 'zap', 'scale', 'coffee', 'award', 'flag', 'folder',
        'bookmark', 'anchor', 'feather', 'key', 'tool', 'printer', 'camera',
        'music', 'video', 'cloud', 'sun', 'moon', 'heart', 'star', 'tag',
        'gift', 'box', 'send', 'mail', 'phone', 'map-pin', 'users', 'user-check',
        'pie-chart', 'trending-up', 'dollar-sign', 'activity', 'lock', 'sliders'
    ];

    public function index(Request $request): Response
    {
        $categories = Category::withCount('courses')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'availableIcons' => self::AVAILABLE_ICONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name'],
            'icon' => ['required', 'string', 'max:30'],
        ]);

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $count = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        \Illuminate\Support\Facades\Cache::forget('catalog_categories');
        Category::create([
            'name' => $data['name'],
            'slug' => $slug,
            'icon' => $data['icon'],
        ]);

        return back()->with('success', "Categoría \"{$data['name']}\" creada con éxito.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name,' . $category->id],
            'icon' => ['required', 'string', 'max:30'],
        ]);

        if ($data['name'] !== $category->name) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug;
            $count = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $baseSlug . '-' . $count++;
            }
            $category->slug = $slug;
        }

        $category->name = $data['name'];
        $category->icon = $data['icon'];
        $category->save();
        \Illuminate\Support\Facades\Cache::forget('catalog_categories');

        return back()->with('success', "Categoría \"{$category->name}\" actualizada.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->courses()->count() > 0) {
            return back()->with('error', "No se puede eliminar la categoría \"{$category->name}\" porque tiene cohortes asociadas.");
        }

        $name = $category->name;
        $category->delete();
        \Illuminate\Support\Facades\Cache::forget('catalog_categories');

        return back()->with('success', "Categoría \"{$name}\" eliminada correctamente.");
    }
}
