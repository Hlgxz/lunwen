<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFile extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'file_type',
        'file_size',
        'file_content'
    ];

    protected $hidden = [
        'file_content'  // 在JSON响应中隐藏文件内容
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 