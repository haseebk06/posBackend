<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $fillable = ['parent_id', 'name', 'description', 'is_active', 'sort_order'];

    public function parent()
    {
        return $this->belongsTo(MenuCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MenuCategory::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }
}
