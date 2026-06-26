<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'Wireless Headphones',
            'description' => 'High-quality wireless headphones with noise cancellation.',
            'price' => 299.99,
            'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop',
        ]);

        Product::create([
            'name' => 'Smart Watch',
            'description' => 'Feature-rich smartwatch with health tracking.',
            'price' => 199.99,
            'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=300&fit=crop',
        ]);

        Product::create([
            'name' => 'Bluetooth Speaker',
            'description' => 'Portable speaker with excellent sound quality.',
            'price' => 89.99,
            'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400&h=300&fit=crop',
        ]);
    }
}
