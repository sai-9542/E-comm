<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    use HasFactory;
     protected $fillable = [
        'name',
        'description',
    'price',
    'thumbnail',
    'post_image',
    ];
    public function customFieldValues()
{
    return $this->hasMany(ProductCustomFieldValue::class);
}

public function customFields()
{
    return $this->hasMany(CustomField::class);
}





}
