@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
    
    <!-- Environment Switcher -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">AffanPay Environment</h2>
        <div class="flex items-center space-x-4">
            <span class="text-gray-600">Current Environment:</span>
            <span class="font-semibold px-3 py-1 rounded-full {{ $environment === 'sandbox' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                {{ ucfirst($environment) }}
            </span>
            <form action="{{ route('admin.switch-environment') }}" method="POST" class="flex items-center space-x-2">
                @csrf
                <select name="environment" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="sandbox" {{ $environment === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                    <option value="live" {{ $environment === 'live' ? 'selected' : '' }}>Live</option>
                </select>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">Switch</button>
            </form>
        </div>
    </div>

    <!-- Credentials Management -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <!-- Sandbox Credentials -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-blue-700">Sandbox Credentials</h2>
            <form action="{{ route('admin.save-credentials') }}" method="POST">
                @csrf
                <input type="hidden" name="environment" value="sandbox">
                <div class="mb-4">
                    <label for="sandbox-email" class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" id="sandbox-email" name="email" value="{{ $sandboxCredentials['email'] }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="mb-6">
                    <label for="sandbox-password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" id="sandbox-password" name="password" autocomplete="new-password" placeholder="{{ $sandboxCredentials['has_password'] ? 'Stored securely. Enter a new password to rotate it.' : 'Enter sandbox password' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="mt-2 text-sm text-gray-500">Saved passwords are no longer displayed. Leave blank to keep the current password.</p>
                </div>
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Save Sandbox Credentials</button>
            </form>
        </div>

        <!-- Live Credentials -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-green-700">Live Credentials</h2>
            <form action="{{ route('admin.save-credentials') }}" method="POST">
                @csrf
                <input type="hidden" name="environment" value="live">
                <div class="mb-4">
                    <label for="live-email" class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" id="live-email" name="email" value="{{ $liveCredentials['email'] }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div class="mb-6">
                    <label for="live-password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" id="live-password" name="password" autocomplete="new-password" placeholder="{{ $liveCredentials['has_password'] ? 'Stored securely. Enter a new password to rotate it.' : 'Enter live password' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <p class="mt-2 text-sm text-gray-500">Saved passwords are encrypted at rest. Leave blank to keep the current password.</p>
                </div>
                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold">Save Live Credentials</button>
            </form>
        </div>
    </div>
    
    <!-- Products Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Products</h2>
            <a href="{{ route('admin.products.create') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg">Add Product</a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-3 px-4">Name</th>
                        <th class="text-left py-3 px-4">Price</th>
                        <th class="text-left py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr class="border-b">
                            <td class="py-3 px-4">{{ $product->name }}</td>
                            <td class="py-3 px-4">RM {{ number_format($product->price, 2) }}</td>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-500 hover:text-blue-600 mr-3">Edit</a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Orders Section -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-6">Orders</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-3 px-4">Order ID</th>
                        <th class="text-left py-3 px-4">Customer</th>
                        <th class="text-left py-3 px-4">Product</th>
                        <th class="text-left py-3 px-4">Total</th>
                        <th class="text-left py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr class="border-b">
                            <td class="py-3 px-4">#{{ $order->id }}</td>
                            <td class="py-3 px-4">{{ $order->customer_name }}</td>
                            <td class="py-3 px-4">{{ $order->product->name }}</td>
                            <td class="py-3 px-4">RM {{ number_format($order->total_amount, 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="px-3 py-1 rounded-full {{ $order->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
