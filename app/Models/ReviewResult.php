<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewResult extends Model
{
    protected $fillable = [
        'student_file_id',
        'reviewer_id',
        'auto_review_status',
        'manual_review_status',
        'auto_review_content',
        'manual_review_comment'
    ];

    public function studentFile()
    {
        return $this->belongsTo(StudentFile::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function arbitrationAppeals()
    {
        return $this->hasMany(ArbitrationAppeal::class);
    }

    public function latestAppeal()
    {
        return $this->hasOne(ArbitrationAppeal::class)->latest();
    }
} 