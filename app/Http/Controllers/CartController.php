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
   /* public function store(Request $request)
{
    $product = Product::findOrFail($request->product_id);
    $customFields = $request->custom ?? [];

    $customData = [];
    $extraPrice = 0;

    foreach ($customFields as $fieldId => $rawValue) {
        $field = CustomField::with('children')->find($fieldId);
        if (!$field) continue;

        $options = array_map('trim', explode(',', $field->options ?? ''));
        $prices = array_map('trim', explode(',', $field->option_prices ?? ''));

        // Normalize input (for select/radio where data is JSON like {"value":"L","price":10})
        if (is_string($rawValue) && str_starts_with($rawValue, '{')) {
            $parsed = json_decode($rawValue, true);
            $value = $parsed['value'] ?? $rawValue;
            $price = floatval($parsed['price'] ?? 0);
        } else {
            $value = $rawValue;
            $price = 0;
        }

        if (in_array($field->type, ['select', 'radio'])) {
            $extraPrice += $price;

            if(!empty($value)){
              $customData[$field->label] = [
                    'value' => $value,
                    'price' => $price
                ];  
            }
            

        } elseif ($field->type === 'checkbox') {
            $selected = is_array($rawValue) ? $rawValue : explode(',', $rawValue);
            $values = [];
            $fieldTotal = 0;

            foreach ($selected as $v) {
                $v = trim($v);
                $index = array_search($v, $options);
                $price = isset($prices[$index]) ? floatval($prices[$index]) : 0;
                $fieldTotal += $price;
                $values[] = ['value' => $v, 'price' => $price];
            }

            $extraPrice += $fieldTotal;
            $customData[$field->label] = $values;

        } elseif ($field->type === 'text') {
            $customData[$field->label] = [
                'value' => $value,
                'price' => 0
            ];
        }

        // ✅ Recursively process children (dependencies)
        foreach ($field->children ?? [] as $child) {
            // echo '<pre>';
            // print_r($child->dependency_value);
            if (!empty($customFields[$child->id])) {
                $childRaw = $customFields[$child->id];

                if (is_string($childRaw) && str_starts_with($childRaw, '{')) {
                    $parsed = json_decode($childRaw, true);
                    $childValue = $parsed['value'] ?? $childRaw;
                    $childPrice = floatval($parsed['price'] ?? 0);
                    print_r($childValue);
                    print_r($childPrice);
                } else {
                    $childValue = $childRaw;
                    $childOptions = array_map('trim', explode(',', $child->options ?? ''));
                    $childPrices = array_map('trim', explode(',', $child->option_prices ?? ''));
                    $index = array_search($childValue, $childOptions);
                    $childPrice = isset($childPrices[$index]) ? floatval($childPrices[$index]) : 0;
                }

                //$extraPrice += $childPrice;

                $customData[$child->dependency_value][$child->label] = [
                    'value' => $childValue,
                    'price' => $childPrice
                ];
                 print_r($customData);
            }
        }
    }

    // 🛒 Store in session
    $cart = session()->get('cart', []);
    $cartItemId = uniqid();

    $cart[$cartItemId] = [
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'custom_price' => $extraPrice,
        'total_price' => $product->price + $extraPrice,
        'custom_fields' => $customData,
        'quantity' => 1
    ];

    echo "<pre>";
        print_r($cart);return;

    session()->put('cart', $cart);

    return redirect()->route('cart.index')->with('success', 'Added to cart successfully.');
}*/
    

    public function store(Request $request)
{   

    $cart = session()->get('cart', []);

    // Filter out item(s) matching the given product_id
    foreach ($cart as $key => $item) {
        if ($item['product_id'] == $request->product_id) {
            unset($cart[$key]);
        }
    }

    //session()->put('cart', $cart);

    $product = Product::findOrFail($request->product_id);
    $customFields = $request->custom ?? [];

    $customData = [];
    $extraPrice = 0;
    $handled = []; // Track handled fields to avoid duplication

    foreach ($customFields as $fieldId => $rawValue) {
        if (in_array($fieldId, $handled)) continue;

        $field = CustomField::with('children')->find($fieldId);
        if (!$field) continue;

        $options = array_map('trim', explode(',', $field->options ?? ''));
        $prices = array_map('trim', explode(',', $field->option_prices ?? ''));

        $value = $rawValue;
        $price = 0;

        // JSON from frontend like { value: "L", price: 20 }
        if (is_string($rawValue) && str_starts_with($rawValue, '{')) {
            $parsed = json_decode($rawValue, true);
            $value = $parsed['value'] ?? $rawValue;
            $price = floatval($parsed['price'] ?? 0);
        }

        if (in_array($field->type, ['select', 'radio'])) {
            $extraPrice += $price;

            if (!empty($value)) {
                $customData[$field->label] = [
                    'value' => $value,
                    'price' => $price
                ];
            }

        } elseif ($field->type === 'checkbox') {
            
            $selected = is_array($rawValue) ? $rawValue : explode(',', $rawValue);
            $values = [];
            $fieldTotal = 0;
            /*foreach(json_decode($rawValue) as $check){
                $checkBoxVal = json_decode($check->value);print_r($check);return;
                $values[] = ['value' => $checkBoxVal->value, 'price' => $checkBoxVal->price];
                $fieldTotal += $checkBoxVal->price;
                $customData[$field->label] = $values;
            }   */

            /*foreach ($selected as $v) {
                $label = $v;
                $price = 0;

                if (is_string($v) && str_starts_with($v, '{')) {
                    $parsed = json_decode($v, true);
                    $label = $parsed['value'] ?? $v;
                    $price = floatval($parsed['price'] ?? 0);
                }

                $values[] = ['value' => $label, 'price' => $price];
                $fieldTotal += $price;
            }*/

            // $extraPrice += $fieldTotal;
            // $customData[$field->label] = $values;

            //chatgpt code ntow roking propwerly
            /*$values = [];
            $fieldTotal = 0;*/

            $decoded = json_decode($rawValue, true); // decode to associative array
            if (is_array($decoded)) {
                foreach ($decoded as $check) {
                    // Ensure both 'value' and 'price' keys exist
                    if (isset($check['value'], $check['price'])) {
                        $values[] = [
                            'value' => $check['value'],
                            'price' => floatval($check['price'])
                        ];
                        $fieldTotal += floatval($check['price']);
                    }
                }
            }

            $extraPrice += $fieldTotal;
            $customData[$field->label] = $values;
            //chatgpt code ntow roking propwerly
            //print_r($customData);return;

        } elseif ($field->type === 'text') {
            $customData[$field->label] = [
                'value' => $value,
                'price' => 0
            ];
        }

        $handled[] = $fieldId;

        // 👇 Process child fields if provided in the request
        foreach ($field->children ?? [] as $child) {
            $childId = $child->id;
            if (!isset($customFields[$childId]) || in_array($childId, $handled)) continue;

            $childRaw = $customFields[$childId];
            $childVal = $childRaw;
            $childPrice = 0;

            if (is_string($childRaw) && str_starts_with($childRaw, '{')) {
                $parsed = json_decode($childRaw, true);
                $childVal = $parsed['value'] ?? $childRaw;
                $childPrice = floatval($parsed['price'] ?? 0);
            } else {
                $childOptions = array_map('trim', explode(',', $child->options ?? ''));
                $childPrices = array_map('trim', explode(',', $child->option_prices ?? ''));
                $index = array_search($childVal, $childOptions);
                $childPrice = isset($childPrices[$index]) ? floatval($childPrices[$index]) : 0;
            }

            $extraPrice += $childPrice;

            $customData[$child->label] = [
                'value' => $childVal,
                'price' => $childPrice
            ];

            $handled[] = $childId;
        }
    }

    $cart = session()->get('cart', []);
    $cartItemId = uniqid();


    $cart[$cartItemId] = [
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'custom_price' => $extraPrice,
        'total_price' => $product->price + $extraPrice,
        'custom_fields' => $customData,
        'quantity' => 1
    ];

// echo "<pre>";
//         print_r($cart);return;

    session()->put('cart', $cart);
    return redirect()->route('cart.index')->with('success', 'Added to cart successfully.');
}


   /* // Edit item
    public function edit($id, Request $request)
    {
        $cart = Cart::where('id', $id)
            ->where('session_id', $request->session()->getId())
            ->firstOrFail();

        $product = Product::with('customFields.children')->findOrFail($cart->product_id);
        $fieldValues = $cart->custom_fields ?? [];

        return view('cart.edit', compact('cart', 'product', 'fieldValues'));
    }
*/


     // Edit item
    public function edit($id, Request $request)
    {
        $cart = session()->get('cart', []);
        $matchedItems = collect($cart)->where('product_id', $id);

        // Example: get first match
        $existedInCart = $matchedItems->first();

        //dd($existedInCart);
        $product = Product::with(['customFields', 'customFieldValues.customField'])->findOrFail($id);
        return view('cart.edit', compact('product', 'existedInCart'));
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