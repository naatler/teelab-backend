<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return Product::where('is_active', true)->with('category')->get();
    }
    public function store(Request $request)
    {
        return Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'price' => $request->price,
            'stock' => $request->stock,
            'image_url' => $request->image_url
        ]);
    }
}
