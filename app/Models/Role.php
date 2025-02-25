<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description'
    ];

    /**
     * 拥有此角色的用户
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
} 