<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with('category');

        if (!$request->has('all')) {
            $query->where('is_active', true);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
    public function store(Request $request)
    {
        return Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'price' => $request->price,
            'stock' => $request->stock,
            'image_url' => $request->image_url,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $product->update([
            'name' => $request->name ?? $product->name,
            'slug' => $request->name ? \Str::slug($request->name) : $product->slug,
            'price' => $request->price ?? $product->price,
            'stock' => $request->stock ?? $product->stock,
            'category_id' => $request->category_id ?? $product->category_id,
            'description' => $request->description ?? $product->description,
            'image_url' => $request->image_url ?? $product->image_url,
            'is_active' => $request->has('is_active') ? $request->is_active : $product->is_active,
        ]);
        
        return $product->fresh();
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return $product;
    }
}
