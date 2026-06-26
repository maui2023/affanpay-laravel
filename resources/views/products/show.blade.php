@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <a href="{{ route('products.index') }}" class="text-blue-500 hover:text-blue-600 mb-4 inline-block">&larr; Back to Products</a>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="md:flex">
            <div class="md:w-1/2">
                @if($product->image)
                    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-96 object-cover">
                @else
                    <div class="w-full h-96 bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-500">No Image</span>
                    </div>
                @endif
            </div>
            <div class="md:w-1/2 p-8">
                <h1 class="text-3xl font-bold mb-4">{{ $product->name }}</h1>
                <p class="text-gray-600 mb-6">{{ $product->description }}</p>
                <div class="text-3xl font-bold text-green-600 mb-8">RM {{ number_format($product->price, 2) }}</div>
                <a href="{{ route('orders.create', $product) }}" class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg text-lg font-semibold">Buy Now</a>
            </div>
        </div>
    </div>
</div>
@endsection