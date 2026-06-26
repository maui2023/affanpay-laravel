@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <a href="{{ route('admin.index') }}" class="text-blue-500 hover:text-blue-600 mb-4 inline-block">&larr; Back to Admin</a>
    
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold mb-6">Edit Product</h1>
        
        <form action="{{ route('admin.products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label for="name" class="block text-gray-700 font-medium mb-2">Name</label>
                <input type="text" id="name" name="name" value="{{ $product->name }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="mb-4">
                <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ $product->description }}</textarea>
            </div>
            
            <div class="mb-4">
                <label for="price" class="block text-gray-700 font-medium mb-2">Price (RM)</label>
                <input type="number" id="price" name="price" step="0.01" value="{{ $product->price }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div class="mb-6">
                <label for="image" class="block text-gray-700 font-medium mb-2">Image URL</label>
                <input type="text" id="image" name="image" value="{{ $product->image }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">Update Product</button>
        </form>
    </div>
</div>
@endsection