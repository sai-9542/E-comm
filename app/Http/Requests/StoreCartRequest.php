<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CustomField;

class StoreCartRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
        ];

        $customFields = CustomField::where('product_id', $this->product_id)->get();

        foreach ($customFields as $field) {
            $key = "custom.{$field->id}";

            // Only apply required rule if field has no parent (or parent is matched)
            if ($field->required && is_null($field->parent_id)) {
                $rules[$key] = 'required';
            }

            if ($field->type === 'checkbox') {
                $rules[$key] = 'array';
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'custom.*.required' => 'This custom field is required.',
            'custom.*.array'    => 'Please select at least one option.',
        ];
    }
}
