<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name',
        'slug',
        'stock',
        'price',
        'category_id',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ]
        ];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function product_categories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function product_images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function order_products()
    {
        return $this->hasMany(OrderProduct::class);
    }
}
