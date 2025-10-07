<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
      protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    protected $fillable = [
        'host','tld','normalized_host','category_id','first_seen_at','last_seen_at'
    ];
    public function category(){ return $this->belongsTo(DomainCategory::class); }
}
