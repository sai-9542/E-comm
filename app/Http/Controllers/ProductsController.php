<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CustomField;
use App\Models\ProductCustomFieldValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
//use Intervention\Image\Facades\Image;
use Intervention\Image\Laravel\Facades\Image;


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
    // 1. Validate input
    $data = $request->validate([
        'name' => 'required|string',
        'description' => 'nullable|string',
        'price' => 'nullable|numeric',
        'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'post_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    // 2. Handle image upload (post image + thumbnail)
    $originalPath = $thumbnailPath = null;
    if ($request->hasFile('post_image')) {
        $original = $request->file('post_image');
        $filename = uniqid() . '.' . $original->getClientOriginalExtension();

        // Save original
        $originalPath = 'products/' . $filename;
        Storage::disk('public')->put($originalPath, file_get_contents($original));

        // Create and save thumbnail
        $thumbnailPath = 'products/thumbnails/' . $filename;
        $thumbnail = Image::read($original)->resize(150, 150, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode();
        Storage::disk('public')->put($thumbnailPath, $thumbnail);
    }

    // 3. Create product
    $product = Product::create([
        'name' => $request->name,
        'description' => $request->description,
        'thumbnail' => $thumbnailPath,
        'post_image' => $originalPath,
        'price' => $request->price,
    ]);

    // 4. Handle custom fields
    $fieldMap = [];

    if ($request->has('fields')) {
        foreach ($request->fields as $tempIndex => $field) {

            // Parse option prices
            $priceArray = [];
            if (!empty($field['option_prices'])) {
                $priceParts = array_map('trim', explode(',', $field['option_prices']));
                foreach ($priceParts as $part) {
                    $priceArray[] = is_numeric($part) ? floatval($part) : 0;
                }
            }

            // Process option images (if any)
            $optionImages = [];
            if (!empty($field['hasImages']) && !empty($field['options'])) {
                $optionList = array_map('trim', explode(',', $field['options']));
                foreach ($optionList as $optionValue) {
                    if (
                        $request->hasFile("field_images.{$tempIndex}.{$optionValue}") &&
                        $request->file("field_images.{$tempIndex}.{$optionValue}")->isValid()
                    ) {
                        $imageFile = $request->file("field_images.{$tempIndex}.{$optionValue}");
                        $path = $imageFile->store('field_option_images', 'public');

                        // Store in associative array for later reference
                        $optionImages[$optionValue] = $path;

                        // If using a separate table instead of JSON:
                        /*
                        FieldOptionImage::create([
                            'custom_field_id' => $created->id, // Only available after field is saved
                            'option_value' => $optionValue,
                            'image_path' => $path,
                        ]);
                        */
                    }
                }
            }

            // Create custom field
            $created = CustomField::create([
                'product_id' => $product->id,
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $field['options'] ?? null,
                'option_prices' => !empty($priceArray) ? json_encode($priceArray) : null,
                'required' => isset($field['required']) ? 1 : 0,
                'option_images' => !empty($optionImages) ? json_encode($optionImages) : null,
            ]);

            $fieldMap[$tempIndex] = $created->id;
        }

        // 5. Resolve parent-child dependencies
        foreach ($request->fields as $tempIndex => $field) {
            if (isset($field['parent_temp']) && $field['parent_temp'] !== null) {
                $realFieldId = $fieldMap[$tempIndex] ?? null;
                $parentRealId = $fieldMap[$field['parent_temp']] ?? null;

                if ($realFieldId && $parentRealId) {
                    CustomField::where('id', $realFieldId)->update([
                        'parent_id' => $parentRealId,
                        'dependency_value' => $field['dependency_value'] ?? null,
                    ]);
                }
            }
        }
    }

    return redirect()->route('products.index')->with('success', 'Product created successfully.');
}



public function storeOld(Request $request)
{   
    $data = $request->validate([
        'name' => 'required|string',
        'description' => 'nullable|string',
        'price' => 'nullable|numeric',
        'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'post_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    //$thumbnail = $request->hasFile('thumbnail') ?  $request->file('thumbnail')->store('products', 'public') : null;

    //$post_image = $request->hasFile('post_image') ? $request->file('post_image')->store('products', 'public') : null;

    $originalPath = $thumbnailPath = null;
    if ($request->hasFile('post_image')) {
        $original = $request->file('post_image');
        $filename = uniqid() . '.' . $original->getClientOriginalExtension();

        // Save original
        $originalPath = 'products/' . $filename;
        Storage::disk('public')->put($originalPath, file_get_contents($original));

        // Create and save thumbnail
        $thumbnailPath = 'products/thumbnails/' . $filename;
        $thumbnail = Image::read($original)->resize(150, 150, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode();
        //$thumbnail = Image::make($original)->resize(150, 150);
        Storage::disk('public')->put($thumbnailPath, $thumbnail);
    }
//print_r($thumbnailPath);return;

    // 1. Create product
    $product = Product::create([
        'name' => $request->name,
        'description' => $request->description,
        'thumbnail' => $thumbnailPath,
        'post_image' => $originalPath,
        'price' => $request->price,
    ]);

    // 2. Store custom fields (with dependency resolution)
    $fieldMap = []; // temp_index => real DB ID

    if ($request->has('fields')) {
        // First pass: create all fields without parent_id
        foreach ($request->fields as $tempIndex => $field) {

            $priceArray = [];
            if (!empty($field['option_prices'])) {
                $priceParts = array_map('trim', explode(',', $field['option_prices']));
                foreach ($priceParts as $part) {
                    $priceArray[] = is_numeric($part) ? floatval($part) : 0;
                }
            }

            $productOptionsImageArray = [];
            $fieldRealId = $product->id;
            $tempFieldIndex  = $tempIndex;
            if (isset($field['hasImages']) && $field['hasImages'] == 1 &&isset($request->field_images[$tempFieldIndex])) {
                $optionList = explode(',', $field['options'] ?? '');
                $optionList = array_map('trim', $optionList);

                foreach ($optionList as $optionValue) {
                    if (
                        isset($request->file('field_images')[$tempFieldIndex][$optionValue]) &&
                        $request->file('field_images')[$tempFieldIndex][$optionValue]->isValid()
                    ) {
                        $imageFile = $request->file('field_images')[$tempFieldIndex][$optionValue];
                        $path = $imageFile->store('field_option_images', 'public'); // Or 'uploads/options' depending on your setup
                        $productOptionsImageArray[] = $path;
                        // Save the image in a separate table (suggested), or update CustomField if storing JSON
                        /*\App\Models\FieldOptionImage::create([
                            'custom_field_id' => $fieldRealId,
                            'option_value' => $optionValue,
                            'image_path' => $path,
                        ]);*/
                    }
                }
            }

            $created = CustomField::create([
                'product_id' => $product->id,
                'label' => $field['label'],
                'type' => $field['type'],
                'options' => $field['options'] ?? null,
                'option_prices' => !empty($priceArray) ? json_encode($priceArray) : null,
                'required' => isset($field['required']) ? 1 : 0,
                'option_images' => json_encode($productOptionsImageArray),
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
        'post_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'price' => 'nullable|numeric',
    ]);

    // Thumbnail logic
    if ($request->hasFile('post_image')) {
        if ($product->thumbnail_path) {
            Storage::delete([$product->thumbnail_path, $product->image_path]);
        }

        $image = $request->file('post_image');
        $filename = uniqid() . '.' . $image->getClientOriginalExtension();

        $originalPath = $image->storeAs('products/original', $filename, 'public');
        $thumbPath = 'products/thumbnails/' . $filename;

        $thumbnail = Image::read($image)->resize(150, 150, function ($c) {
            $c->aspectRatio();
            $c->upsize();
        })->encode();

        Storage::disk('public')->put($thumbPath, $thumbnail);

        $product->post_image = $originalPath;
        $product->thumbnail = $thumbPath;
    }

    // Update base fields
    $product->name = $validated['name'];
    $product->description = $validated['description'] ?? null;
    $product->price = $validated['price'];
    $product->save();

    $savedIds = [];
    $tempIndexToRealId = [];
    $imageInputs = $request->file('field_images', []);

    foreach ($request->fields as $i => $field) {
        if (isset($field['deleted']) && $field['deleted'] == 1) {
            if (!empty($field['id'])) {
                CustomField::where('id', $field['id'])->delete();
            }
            continue;
        }

        // Parse prices
        $priceArray = [];
        if (!empty($field['option_prices'])) {
            $priceParts = array_map('trim', explode(',', $field['option_prices']));
            foreach ($priceParts as $part) {
                $priceArray[] = is_numeric($part) ? floatval($part) : 0;
            }
        }

        $existingField = !empty($field['id']) ? CustomField::find($field['id']) : new CustomField();

        $existingField->product_id = $product->id;
        $existingField->label = $field['label'];
        $existingField->type = $field['type'];
        $existingField->options = $field['options'] ?? null;
        $existingField->option_prices = !empty($priceArray) ? json_encode($priceArray) : null;
        $existingField->required = isset($field['required']) ? 1 : 0;
        $existingField->parent_id = null;
        $existingField->dependency_value = null;

        // --- 🖼️ Option images processing ---
        $index = $field['_temp_index'];
        $optionImages = [];

        if(isset($field['hasImages'])){
            if (!empty($imageInputs[$index])) {
                foreach ($imageInputs[$index] as $optionKey => $file) {
                    if ($file && $file->isValid()) {
                        $storedPath = $file->store('custom_field_options', 'public');
                        $optionImages[$optionKey] = $storedPath;
                    }
                }
            }

            // 🛠️ Merge with old images if not replaced
            if ($existingField->option_images) {
                $oldImages = json_decode($existingField->option_images, true) ?? [];
                foreach ($oldImages as $key => $path) {
                    if (!isset($optionImages[$key])) {
                        $optionImages[$key] = $path;
                    }
                }
            }
        }

        $existingField->option_images = !empty($optionImages) ? json_encode($optionImages) : null;
        $existingField->save();

        $savedIds[] = $existingField->id;
        if (isset($field['_temp_index'])) {
            $tempIndexToRealId[$field['_temp_index']] = $existingField->id;
        }
    }

    // Phase 2: handle parent-child dependency
    foreach ($request->fields as $field) {
        if ((isset($field['deleted']) && $field['deleted'] == 1)) {
            continue;
        }

        if (array_key_exists('parent_temp', $field) && $field['parent_temp'] !== '') {
            $realFieldId = !empty($field['id'])
                ? $field['id']
                : ($tempIndexToRealId[$field['_temp_index']] ?? null);

            $parentId = $tempIndexToRealId[$field['parent_temp']] ?? null;

            if ($parentId && $realFieldId) {
                CustomField::where('id', $realFieldId)->update([
                    'parent_id' => $parentId,
                    'dependency_value' => $field['dependency_value'] ?? null,
                ]);
            }
        }
    }

    // Phase 3: Delete removed
    $product->customFields()->whereNotIn('id', $savedIds)->delete();

    return redirect()->route('products.edit', $product->id)->with('success', 'Product updated successfully.');
}

public function updateOld(Request $request, $id)
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
        'post_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'price' => 'nullable|numeric',
    ]);

    if ($request->hasFile('post_image')) {
        // Delete old files if needed
        if ($product->thumbnail_path) {
            Storage::delete([$product->thumbnail_path, $product->image_path]);
        }

        $image = $request->file('post_image');
        $filename = uniqid() . '.' . $image->getClientOriginalExtension();

        // Store original
        $originalPath = $image->storeAs('products/original', $filename, 'public');

        // Create thumbnail
        $thumbPath = 'products/thumbnails/' . $filename;
        $thumbnail = Image::read($image)->resize(150, 150, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode();
        Storage::disk('public')->put($thumbPath, $thumbnail);

        // Save paths
        $product->post_image = $originalPath;
        $product->thumbnail = $thumbPath;
    }

    $product->name = $validated['name'];
    $product->description = $validated['description'] ?? null;
    $product->price = $validated['price'];
    $product->save();

    /*$product->update([
        'name' => $request->name,
        'description' => $request->description,
        'price' => $request->price,
    ]);*/

    $savedIds = [];
$tempIndexToRealId = [];

foreach ($request->fields as $i => $field) {
    // Skip if marked deleted
    if (isset($field['deleted']) && $field['deleted'] == 1) {
        if (!empty($field['id'])) {
            // If field exists in DB, delete it
            CustomField::where('id', $field['id'])->delete();
        }
        continue;
    }

    $priceArray = [];
            if (!empty($field['option_prices'])) {
                $priceParts = array_map('trim', explode(',', $field['option_prices']));
                foreach ($priceParts as $part) {
                    $priceArray[] = is_numeric($part) ? floatval($part) : 0;
                }
            }

    // Create or update logic (same as before)
    $data = [
        'product_id' => $product->id,
        'label' => $field['label'],
        'type' => $field['type'],
        'options' => $field['options'] ?? null,
        'option_prices' => !empty($priceArray) ? json_encode($priceArray) : null,
        'required' => isset($field['required']) ? 1 : 0,
        'parent_id' => null,
        'dependency_value' => null,
    ];

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

// Second pass: assign parent_id and dependency_value
foreach ($request->fields as $field) {
    if ((isset($field['deleted']) && $field['deleted'] == 1)) {
        continue;
    }

    if (array_key_exists('parent_temp', $field) && $field['parent_temp'] !== '') {
        $realFieldId = !empty($field['id'])
            ? $field['id']
            : ($tempIndexToRealId[$field['_temp_index']] ?? null);

        $parentId = $tempIndexToRealId[$field['parent_temp']] ?? null;

        $dependencyValue = isset($field['dependency_value']) && $field['dependency_value'] !== ''
            ? $field['dependency_value']
            : null;

        if ($parentId && $realFieldId) {
            CustomField::where('id', $realFieldId)->update([
                'parent_id' => $parentId,
                'dependency_value' => $dependencyValue,
            ]);
        }
    }
}


// Third pass: delete removed fields (optional)
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
