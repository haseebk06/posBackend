<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'user_email', 'action', 'entity_type',
        'entity_id', 'reason', 'entity_snapshot', 'created_at',
    ];

    protected $casts = [
        'entity_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}