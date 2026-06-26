<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\AffanPayService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $affanPay;

    public function __construct(AffanPayService $affanPay)
    {
        $this->affanPay = $affanPay;
    }

    public function index()
    {
        $products = Product::all();
        $orders = Order::with('product')->latest()->get();
        $environment = $this->affanPay->getEnvironment();
        $sandboxCredentials = $this->affanPay->getCredentials('sandbox');
        $liveCredentials = $this->affanPay->getCredentials('live');
        return view('admin.index', compact('products', 'orders', 'environment', 'sandboxCredentials', 'liveCredentials'));
    }

    public function productsCreate()
    {
        return view('admin.products.create');
    }

    public function productsStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'image' => 'nullable|string',
        ]);

        Product::create($validated);

        return redirect()->route('admin.index')->with('success', 'Product created successfully.');
    }

    public function productsEdit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function productsUpdate(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'image' => 'nullable|string',
        ]);

        $product->update($validated);

        return redirect()->route('admin.index')->with('success', 'Product updated successfully.');
    }

    public function productsDestroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.index')->with('success', 'Product deleted successfully.');
    }

    public function switchEnvironment(Request $request)
    {
        $validated = $request->validate([
            'environment' => 'required|in:sandbox,live',
        ]);

        $this->affanPay->setEnvironment($validated['environment']);

        return redirect()->route('admin.index')->with('success', 'Environment switched successfully.');
    }

    public function saveCredentials(Request $request)
    {
        $validated = $request->validate([
            'environment' => 'required|in:sandbox,live',
            'email' => 'required|email',
            'password' => 'nullable|string|max:255',
        ]);

        $this->affanPay->setCredentials(
            $validated['environment'],
            $validated['email'],
            $validated['password'] ?? null
        );

        return redirect()->route('admin.index')->with('success', ucfirst($validated['environment']) . ' credentials saved successfully.');
    }
}
