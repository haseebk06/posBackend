<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSectionId extends Model
{
    protected $table = 'menu_section_items';
    protected $fillable = ['section_id', 'menu_item_id'];

    public function section()
    {
        return $this->belongsTo(MenuSection::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
