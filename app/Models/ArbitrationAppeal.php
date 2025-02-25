<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArbitrationAppeal extends Model
{
    protected $fillable = [
        'review_result_id',
        'student_id',
        'arbitrator_id',
        'appeal_content',
        'arbitration_opinion',
        'status',
        'result',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // 关联评审结果
    public function reviewResult()
    {
        return $this->belongsTo(ReviewResult::class);
    }

    // 关联学生
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // 关联仲裁人
    public function arbitrator()
    {
        return $this->belongsTo(User::class, 'arbitrator_id');
    }

    // 判断是否可以编辑
    public function canEdit()
    {
        return $this->status === 'pending';
    }

    // 判断是否已完成
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
} 