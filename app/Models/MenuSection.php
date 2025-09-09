<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSection extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'sort_order'];

    public function items()
    {
        return $this->belongsToMany(MenuItem::class, 'menu_section_items', 'section_id', 'menu_item_id')
                    ->withTimestamps()
                    ->withPivot('id');
    }
}