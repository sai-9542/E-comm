<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCustomFieldValue extends Model
{
    protected $fillable = ['product_id', 'custom_field_id', 'value'];

    public function product()
{
    return $this->belongsTo(Product::class);
}

public function customField()
{
    return $this->belongsTo(CustomField::class);
}

}


