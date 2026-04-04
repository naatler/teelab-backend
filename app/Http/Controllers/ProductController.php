<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return Product::where('is_active', true)->get();
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
