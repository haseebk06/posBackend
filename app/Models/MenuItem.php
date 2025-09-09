<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = ['category_id', 'name', 'description', 'is_active', 'sort_order'];

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function section()
    {
        return $this->belongsTo(MenuSection::class, 'section_id');
    }
    
    public function variants()
    {
        return $this->hasMany(MenuItemVariant::class);
    }

    public function addons()
    {
        return $this->hasMany(MenuItemAddon::class);
    }
    
    public function sections()
    {
        return $this->belongsToMany(MenuSection::class, 'menu_section_items', 'menu_item_id', 'section_id')
                    ->withTimestamps();
    }
}
