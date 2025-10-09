<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainCategory extends Model
{
    protected $table = 'domain_categories';

    protected $fillable = ['slug', 'name', 'files_path', 'updated_from_fs_at', 'priority'];

    public function domains()
    {
        // All domains via pivot
        return $this->belongsToMany(Domain::class, 'domain_category_domain')
            ->withTimestamps();
    }
}
