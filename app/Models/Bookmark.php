<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = [
        'name', 'type', 'url', 'icon', 'parent_id'
    ];

    public function children()
    {
        return $this->hasMany(Bookmark::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Bookmark::class, 'parent_id');
    }
}
