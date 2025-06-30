<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\CustomField;

class CartController extends Controller
{
    // Show all cart items (for session user)
    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();

        $cart = Cart::where('session_id', $sessionId)->get();
        $fieldMap = CustomField::pluck('label', 'id')->toArray();

        return view('user.cart', compact('cart', 'fieldMap'));
    }

    // Store new item to cart
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'custom' => 'nullable|array'
        ]);

        $product = Product::findOrFail($request->product_id);

        Cart::create([
            'session_id' => $request->session()->getId(),
            'product_id' => $product->id,
            'quantity' => 1,
            'custom_fields' => $request->custom ?? [],
        ]);

        return redirect()->route('cart.index')->with('success', 'Added to cart successfully.');
    }

    // Edit item
    public function edit($id, Request $request)
    {
        $cart = Cart::where('id', $id)
            ->where('session_id', $request->session()->getId())
            ->firstOrFail();

        $product = Product::with('customFields.children')->findOrFail($cart->product_id);
        $fieldValues = $cart->custom_fields ?? [];

        return view('cart.edit', compact('cart', 'product', 'fieldValues'));
    }

    // Update cart item
     public function update(Request $request, $id)
    {
        $request->validate([
            //'quantity' => 'required|integer|min:1',
            'custom' => 'nullable|array'
        ]);

        $item = Cart::where('id', $id)
            ->where('session_id', $request->session()->getId())
            ->firstOrFail();

        $item->update([
            'quantity' => 1, //$request->quantity,
            'custom_fields' => $request->custom ?? [],
        ]);

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    // Remove cart item
    public function destroy($id, Request $request)
    {
        $item = Cart::where('id', $id)
            ->where('session_id', $request->session()->getId())
            ->first();
            
        if ($item) {
            $item->delete();
            return redirect()->route('cart.index')->with('success', 'Item removed.');
        }

        return redirect()->route('cart.index')->with('error', 'Item not found.');
    }
}