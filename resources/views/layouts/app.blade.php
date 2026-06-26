<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AffanPay Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('products.index') }}" class="text-xl font-bold text-gray-800">AffanPay Demo</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900">Products</a>
                    <a href="{{ route('admin.index') }}" class="text-gray-600 hover:text-gray-900">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="py-4">
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>