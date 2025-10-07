<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainCategory extends Model
{
    protected $table = 'domain_categories';
    protected $fillable = ['slug','name','files_path','updated_from_fs_at'];
    public function domains(){ return $this->hasMany(Domain::class); }
}
