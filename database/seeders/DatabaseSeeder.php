<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::create([
            'id' => Str::uuid(),
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'phone' => '08123456789',
            'role' => 'admin',
        ]);

        // Create Regular User
        User::create([
            'id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('user123'),
            'phone' => '08123456788',
            'role' => 'user',
        ]);

        // Create Categories
        $electronics = Category::create([
            'id' => Str::uuid(),
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and gadgets',
        ]);

        $fashion = Category::create([
            'id' => Str::uuid(),
            'name' => 'Fashion',
            'slug' => 'fashion',
            'description' => 'Clothing and accessories',
        ]);

        $sports = Category::create([
            'id' => Str::uuid(),
            'name' => 'Sports & Outdoors',
            'slug' => 'sports-outdoors',
            'description' => 'Sports equipment and outdoor gear',
        ]);

        $books = Category::create([
            'id' => Str::uuid(),
            'name' => 'Books',
            'slug' => 'books',
            'description' => 'Books and magazines',
        ]);

        // Create Sample Products
        $products = [
            [
                'category_id' => $sports->id,
                'name' => 'Golf Club Set',
                'slug' => 'golf-club-set',
                'description' => 'Professional golf club set for serious players',
                'price' => 8000000,
                'stock' => 20,
                'image_url' => 'https://images.unsplash.com/photo-1530028828-25e8270af4bc?w=400',
            ],
            [
                'category_id' => $sports->id,
                'name' => 'Electric Golf Cart',
                'slug' => 'electric-golf-cart',
                'description' => 'Eco-friendly electric golf cart',
                'price' => 45000000,
                'stock' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1535131749006-b7f58c99034b?w=400',
            ],
            [
                'category_id' => $sports->id,
                'name' => 'Premium Golf Balls (Set of 12)',
                'slug' => 'premium-golf-balls',
                'description' => 'High-performance golf balls for better control',
                'price' => 500000,
                'stock' => 200,
                'image_url' => 'https://images.unsplash.com/photo-1622428051717-dcd8412959de?w=400',
            ],
            [
                'category_id' => $books->id,
                'name' => 'Clean Code',
                'slug' => 'clean-code',
                'description' => 'A handbook of agile software craftsmanship',
                'price' => 450000,
                'stock' => 150,
                'image_url' => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?w=400',
            ],
        ];

        foreach ($products as $product) {
            Product::create(array_merge(['id' => Str::uuid()], $product));
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@example.com / admin123');
        $this->command->info('User: user@example.com / user123');
    }
}