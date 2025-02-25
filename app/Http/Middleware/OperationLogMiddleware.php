<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\OperationLog;
use Illuminate\Http\Request;

class OperationLogMiddleware
{
    protected $sensitiveFields = [
        'password',
        'password_confirmation',
        'token',
        'secret'
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 只记录认证用户的操作
        if (auth()->check()) {
            $this->logOperation($request);
        }

        return $response;
    }

    protected function logOperation(Request $request)
    {
        // 获取路由信息
        $route = $request->route();
        $action = $route ? $route->getActionName() : 'unknown';
        
        // 从控制器动作名称中提取模块和操作
        $actionParts = explode('@', class_basename($action));
        $controller = $actionParts[0] ?? 'unknown';
        $method = $actionParts[1] ?? 'unknown';
        
        // 获取请求数据（排除敏感信息）
        $requestData = $request->except($this->sensitiveFields);
        
        // 获取请求方法
        $httpMethod = $request->method();

        // 根据 HTTP 方法推断操作类型
        $operationType = $this->getOperationType($httpMethod, $method);

        OperationLog::create([
            'user_id' => auth()->id(),
            'module' => strtolower(str_replace('Controller', '', $controller)),
            'action' => $operationType,
            'description' => $this->getDescription($httpMethod, $method),
            'details' => [
                'route' => $request->path(),
                'method' => $httpMethod,
                'input' => $requestData,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }

    protected function getOperationType($httpMethod, $method)
    {
        if (str_contains($method, 'index') || str_contains($method, 'show')) {
            return 'view';
        }
        
        switch ($httpMethod) {
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return strtolower($method);
        }
    }

    protected function getDescription($httpMethod, $method)
    {
        $actionMap = [
            'index' => '查看列表',
            'show' => '查看详情',
            'store' => '创建',
            'update' => '更新',
            'destroy' => '删除',
        ];

        return $actionMap[$method] ?? ucfirst($method);
    }
} 