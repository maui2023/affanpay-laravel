@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold mb-8">Our Products</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($products as $product)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                @if($product->image)
                    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                @else
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-500">No Image</span>
                    </div>
                @endif
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-2">{{ $product->name }}</h2>
                    <p class="text-gray-600 mb-4">{{ $product->description }}</p>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-green-600">RM {{ number_format($product->price, 2) }}</span>
                        <a href="{{ route('products.show', $product) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">View</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection