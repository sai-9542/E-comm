<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'label',
        'type',
        'options',
        'parent_id',
        'dependency_value',
        'required',
        'option_prices'
    ];
    

    protected $casts = [
        'options' => 'array',
    ];

    // Relationship to parent custom field (if any)
    public function parent()
    {
        return $this->belongsTo(CustomField::class, 'parent_id');
    }

    // Relationship to child custom fields (if any)
    public function children()
    {
        return $this->hasMany(CustomField::class, 'parent_id');
    }

    // The product this custom field belongs to
    public function product()
{
    return $this->belongsTo(Product::class);
}

}
