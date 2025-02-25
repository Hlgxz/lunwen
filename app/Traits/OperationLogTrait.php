<?php

namespace App\Traits;

use App\Models\OperationLog;

trait OperationLogTrait
{
    protected function logOperation($action, $description, $details = null, $module = null)
    {
        // 如果没有指定模块名，则从当前控制器名称中提取
        if (!$module) {
            $className = class_basename(get_class($this));
            $module = strtolower(str_replace('Controller', '', $className));
        }

        OperationLog::create([
            'user_id' => auth()->id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
} 