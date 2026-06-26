@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <a href="{{ route('products.show', $product) }}" class="text-blue-500 hover:text-blue-600 mb-4 inline-block">&larr; Back to Product</a>
    
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold mb-6">Checkout - {{ $product->name }}</h1>
        
        <div class="mb-6 p-4 bg-gray-50 rounded">
            <p class="text-lg"><strong>Price:</strong> RM {{ number_format($product->price, 2) }}</p>
        </div>
        
        <form action="{{ route('orders.store', $product) }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label for="customer_name" class="block text-gray-700 font-medium mb-2">Name</label>
                <input type="text" id="customer_name" name="customer_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="mb-4">
                <label for="customer_email" class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" id="customer_email" name="customer_email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="mb-4">
                <label for="customer_phone" class="block text-gray-700 font-medium mb-2">Phone</label>
                <input type="text" id="customer_phone" name="customer_phone" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="mb-6">
                <label for="quantity" class="block text-gray-700 font-medium mb-2">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" value="1" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold">Place Order</button>
        </form>
    </div>
</div>
@endsection