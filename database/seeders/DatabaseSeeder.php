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
                'category_id' => $electronics->id,
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'description' => 'Latest iPhone with amazing features and performance',
                'price' => 15000000,
                'stock' => 50,
                'image_url' => 'https://images.unsplash.com/photo-1696446702514-09b85c2d6293?w=400',
            ],
            [
                'category_id' => $electronics->id,
                'name' => 'MacBook Pro M3',
                'slug' => 'macbook-pro-m3',
                'description' => 'Powerful laptop for professionals and creators',
                'price' => 25000000,
                'stock' => 30,
                'image_url' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400',
            ],
            [
                'category_id' => $electronics->id,
                'name' => 'Sony WH-1000XM5',
                'slug' => 'sony-wh-1000xm5',
                'description' => 'Premium noise-cancelling headphones',
                'price' => 4500000,
                'stock' => 75,
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400',
            ],
            [
                'category_id' => $fashion->id,
                'name' => 'Nike Air Jordan',
                'slug' => 'nike-air-jordan',
                'description' => 'Classic basketball sneakers with iconic style',
                'price' => 2500000,
                'stock' => 100,
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400',
            ],
            [
                'category_id' => $fashion->id,
                'name' => 'Levi\'s Denim Jacket',
                'slug' => 'levis-denim-jacket',
                'description' => 'Timeless denim jacket for any occasion',
                'price' => 1200000,
                'stock' => 60,
                'image_url' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=400',
            ],
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