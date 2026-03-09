<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->ordered()
            ->with('children')
            ->parentOnly()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    public function show($id): JsonResponse
    {
        $category = Category::with(['products', 'children'])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $category,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|unique:categories,name|max:255',
            'description' => 'nullable|string',
            'slug'        => 'nullable|string|unique:categories,slug',
            'image'       => 'nullable|string',
            'icon'        => 'nullable|string',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
            'parent_id'   => 'nullable|exists:categories,id',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data'    => $category,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|string|unique:categories,name,'.$id.'|max:255',
            'description' => 'nullable|string',
            'slug'        => 'nullable|string|unique:categories,slug,'.$id,
            'image'       => 'nullable|string',
            'icon'        => 'nullable|string',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
            'parent_id'   => 'nullable|exists:categories,id',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data'    => $category,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}