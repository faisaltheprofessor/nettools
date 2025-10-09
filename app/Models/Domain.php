<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $table = 'domains';

    protected $fillable = [
        'host',
        'tld',
        'normalized_host',
        'category_id',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(DomainCategory::class, 'category_id');
    }

    public function categories()
    {
        return $this->belongsToMany(
            DomainCategory::class,
            'domain_category_domain',
            'domain_id',
            'domain_category_id'
        )->withPivot([]);
    }
}
