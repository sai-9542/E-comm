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
                        'required' => isset($field['required']) ? 1 : 0,
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

public function edit($id)
    {
        $product = Product::with(['customFields.children'])->findOrFail($id);
        return view('products.edit', compact('product'));

    }



 public function update(Request $request, $id)
{
    $product = Product::with('customFields')->findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'fields' => 'array',
        'fields.*.label' => 'required|string|max:255',
        'fields.*.type' => 'required|in:text,select,radio,checkbox',
        'fields.*.options' => 'nullable|string',
        'fields.*.required' => 'nullable|boolean',
    ]);

    $product->update([
        'name' => $request->name,
        'description' => $request->description,
    ]);

    $savedIds = [];
    $tempIndexToRealId = [];

    // First pass: create/update all fields, store _temp_index => real_id
    foreach ($request->fields as $i => $field) {
        $data = [
            'product_id' => $product->id,
            'label' => $field['label'],
            'type' => $field['type'],
            'options' => $field['options'] ?? null,
            'required' => isset($field['required']) ? 1 : 0,
            'parent_id' => null, // default to no parent
            'dependency_value' => $field['dependency_value'] ?? null,
        ];

        // Parent linking will be updated in second pass
        if (!empty($field['id'])) {
            $cf = CustomField::find($field['id']);
            if ($cf) {
                $cf->update($data);
                $savedIds[] = $cf->id;
                if (isset($field['_temp_index'])) {
                    $tempIndexToRealId[$field['_temp_index']] = $cf->id;
                }
            }
        } else {
            $cf = CustomField::create($data);
            $savedIds[] = $cf->id;
            if (isset($field['_temp_index'])) {
                $tempIndexToRealId[$field['_temp_index']] = $cf->id;
            }
        }
    }

    // Second pass: now assign parent_id using temp index
    foreach ($request->fields as $field) {
        if (!empty($field['parent_temp'])) {
            $realFieldId = !empty($field['id']) ? $field['id'] : null;
            $parentId = $tempIndexToRealId[$field['parent_temp']] ?? null;

            if ($parentId && $realFieldId) {
                CustomField::where('id', $realFieldId)->update([
                    'parent_id' => $parentId,
                    'dependency_value' => $field['dependency_value'] ?? null,
                ]);
            } elseif ($parentId && empty($realFieldId)) {
                // Create child now with parent_id
                $cf = CustomField::create([
                    'product_id' => $product->id,
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'options' => $field['options'] ?? null,
                    'required' => isset($field['required']) ? 1 : 0,
                    'parent_id' => $parentId,
                    'dependency_value' => $field['dependency_value'] ?? null,
                ]);
                $savedIds[] = $cf->id;
            }
        }
    }

    // Remove deleted fields
    $product->customFields()->whereNotIn('id', $savedIds)->delete();

    return redirect()->route('products.edit', $product->id)->with('success', 'Product updated successfully.');
}


    // Delete a product
public function destroy($id)
{
    $product = Product::findOrFail($id);
    $product->delete();

    return response()->json(null, 204);
}
}
