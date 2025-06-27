<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CustomField;
use App\Models\ProductCustomFieldValue;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    // List all products
    public function index()
    {
    $products = Product::latest()->get(); // or with('customFieldValues') if needed
    return view('products.index', compact('products'));
}


public function create()
{
    $customFields = CustomField::whereNull('parent_id')->with('children')->get();

    return view('products.create', compact('customFields'));
}



    // Show a single product
public function show($id)
{
   // $product = Product::with('customFieldValues.customField')->findOrFail($id);
$product = Product::with(['customFields', 'customFieldValues.customField'])->findOrFail($id);

   // print_r($product);
    return view('products.show', compact('product'));
}




public function store(Request $request)
{
    // 1. Create product
    $product = Product::create([
        'name' => $request->name,
        'description' => $request->description,
    ]);

    // 2. Store custom fields (with dependency resolution)
    $fieldMap = []; // temp_index => real DB ID

    if ($request->has('fields')) {
        // First pass: create all fields without parent_id
        foreach ($request->fields as $tempIndex => $field) {
            $created = CustomField::create([
                'product_id' => $product->id,
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $field['options'] ?? null,
                // leave parent_id NULL for now
            ]);
            $fieldMap[$tempIndex] = $created->id;
        }

        // Second pass: update parent_id where applicable
        foreach ($request->fields as $tempIndex => $field) {
            if (isset($field['parent_temp']) && $field['parent_temp'] !== null) {
                $realFieldId = $fieldMap[$tempIndex] ?? null;
                $parentTempIndex = $field['parent_temp'];
                $parentRealId = $fieldMap[$parentTempIndex] ?? null;

                if ($realFieldId && $parentRealId) {
                    CustomField::where('id', $realFieldId)->update([
                        'parent_id' => $parentRealId,
                        'dependency_value' => $field['dependency_value'] ?? null,
                    ]);
                }
            }
        }
    }

    // 3. Save user values if needed (not shown here)

    return redirect()->route('products.index')->with('success', 'Product created successfully.');
}





    // Update a product
public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'price' => 'sometimes|required|numeric',
    ]);

    $product->update($validated);
    return response()->json($product);
}

    // Delete a product
public function destroy($id)
{
    $product = Product::findOrFail($id);
    $product->delete();

    return response()->json(null, 204);
}
}
