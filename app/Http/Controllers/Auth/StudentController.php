<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StudentFile;
use App\Models\ReviewResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ArbitrationAppeal;

class StudentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 最大10MB
        ]);

        try {
            $file = $request->file('file');
            
            // 检查文件是否有效
            if (!$file->isValid()) {
                throw new \Exception('文件上传过程中发生错误');
            }

            // 获取文件路径并检查是否存在
            $path = $file->getRealPath();
            if (!file_exists($path)) {
                throw new \Exception('无法访问上传的文件');
            }

            // 读取文件内容并进行 base64 编码
            $fileContent = base64_encode(file_get_contents($path));
            if ($fileContent === false) {
                throw new \Exception('文件内容读取失败');
            }

            // 保存文件信息和编码后的内容到数据库
            $studentFile = StudentFile::create([
                'user_id' => auth()->id(),
                'original_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'file_content' => $fileContent
            ]);

            $this->aiCheck($studentFile->id);

            // 返回不包含文件内容的信息
            return response()->json([
                'message' => '文件上传成功',
                'file' => [
                    'id' => $studentFile->id,
                    'original_name' => $studentFile->original_name,
                    'file_type' => $studentFile->file_type,
                    'file_size' => $studentFile->file_size,
                    'created_at' => $studentFile->created_at
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => '文件上传失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download($id)
    {
        try {
            // 查找文件并验证权限
            $file = StudentFile::where('user_id', auth()->id())
                ->where('id', $id)
                ->firstOrFail();

            // 检查文件内容是否存在
            if (empty($file->file_content)) {
                throw new \Exception('文件内容不存在');
            }

            // 解码文件内容
            $fileContent = base64_decode($file->file_content);
            if ($fileContent === false) {
                throw new \Exception('文件解码失败');
            }

            // 返回文件下载响应
            return response($fileContent)
                ->header('Content-Type', $file->file_type)
                ->header('Content-Disposition', 'attachment; filename="' . $file->original_name . '"')
                ->header('Content-Length', strlen($fileContent));

        } catch (\Exception $e) {
            return response()->json([
                'message' => '文件下载失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function scopeUserAccess($query)
    {
        if (auth()->user()->role_id != 5) {
            $query->where('user_id', auth()->id());
        }
        return $query;
    }

    public function index()
    {
        $files = StudentFile::query()
            ->tap([$this, 'scopeUserAccess'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($files);
    }

    public function destroy($id)
    {
        $file = StudentFile::where('user_id', auth()->id())
            ->findOrFail($id);

        $file->delete();

        return response()->json(['message' => '文件删除成功']);
    }

    public function thesisList(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'title' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:pending,approved,rejected'
        ]);

        $query = StudentFile::query();
        $this->scopeUserAccess($query);

        // 标题搜索
        if ($request->filled('title')) {
            $query->where('original_name', 'like', '%' . $request->title . '%');
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 分页
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        $total = $query->count();
        $items = $query->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->with('user:id,name')
            ->get()
            ->map(function ($item) {
                $item->username = $item->user->name;
                unset($item->user);
                return $item;
            });

        return response()->json([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ]);
    }

    public function aiCheck($id)
    {
        try {
            $file = StudentFile::where('user_id', auth()->id())
                ->where('id', $id)
                ->firstOrFail();

            // 创建评审结果记录
            ReviewResult::create([
                'student_file_id' => $file->id,
                'auto_review_status' => 'success',
                'auto_review_content' => 'test111'
            ]);

            return response()->json([
                'message' => 'AI检测完成',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'AI检测失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function reviewList(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'title' => 'nullable|string|max:255',
            'auto_review_status' => 'nullable|string|in:pending,success,failed',
            'manual_review_status' => 'nullable|string|in:pending,approved,rejected'
        ]);

        $query = ReviewResult::whereHas('studentFile', function($query) {
            $this->scopeUserAccess($query);
        });

        // 标题搜索
        if ($request->filled('title')) {
            $query->whereHas('studentFile', function($query) use ($request) {
                $query->where('original_name', 'like', '%' . $request->title . '%');
            });
        }

        // 自动评审状态筛选
        if ($request->filled('auto_review_status')) {
            $query->where('auto_review_status', $request->auto_review_status);
        }

        // 人工审核状态筛选
        if ($request->filled('manual_review_status')) {
            $query->where('manual_review_status', $request->manual_review_status);
        }

        // 分页
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        $total = $query->count();
        $items = $query->with(['studentFile:id,original_name,file_type,file_size,created_at'])
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'file_id' => $item->student_file_id,
                    'file_name' => $item->studentFile->original_name,
                    'file_type' => $item->studentFile->file_type,
                    'file_size' => $item->studentFile->file_size,
                    'auto_review_status' => $item->auto_review_status,
                    'auto_review_content' => $item->auto_review_content,
                    'manual_review_status' => $item->manual_review_status,
                    'manual_review_comment' => $item->manual_review_comment,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];
            });

        return response()->json([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ]);
    }

    public function getAppeals(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'title' => 'nullable|string',
            'status' => 'nullable|string|in:pending,processing,completed',
            'result' => 'nullable|string|in:pending,approved,rejected'
        ]);

        try {
            $query = ArbitrationAppeal::query();
            if (auth()->user()->role_id !== 5) {
                $query->where('student_id', auth()->id());
            }

            // 标题搜索
            if ($request->filled('title')) {
                $query->whereHas('reviewResult.studentFile', function($q) use ($request) {
                    $q->where('original_name', 'like', '%' . $request->title . '%');
                });
            }

            // 状态筛选
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // 结果筛选
            if ($request->filled('result')) {
                $query->where('result', $request->result);
            }

            $total = $query->count();
            $items = $query->orderBy('created_at', 'desc')
                ->offset(($request->input('page', 1) - 1) * $request->input('pageSize', 10))
                ->limit($request->input('pageSize', 10))
                ->get();

            return response()->json([
                'items' => $items,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => '获取申诉列表失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function submitAppeal(Request $request, $reviewId)
    {
        $request->validate([
            'appeal_content' => 'required|string|max:1000',
        ]);

        try {
            // 检查评审结果是否存在且属于当前用户
            $reviewResult = ReviewResult::whereHas('studentFile', function($query) {
                $query->where('user_id', auth()->id());
            })->findOrFail($reviewId);

            // 检查是否已有待处理的申诉
            $existingAppeal = $reviewResult->arbitrationAppeals()
                ->whereIn('status', ['pending', 'processing'])
                ->first();

            if ($existingAppeal) {
                return response()->json([
                    'message' => '已有待处理的申诉，请等待处理完成后再提交新的申诉',
                    'status' => 'error'
                ], 422);
            }

            // 创建新的申诉
            $appeal = ArbitrationAppeal::create([
                'review_result_id' => $reviewId,
                'student_id' => auth()->id(),
                'appeal_content' => $request->appeal_content,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => '申诉提交成功',
                'status' => 'success',
                'data' => $appeal
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => '申诉提交失败',
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleAppeal(Request $request, $id)
    {
        $request->validate([
            'result' => 'required|string|in:approved,rejected',
            'arbitration_opinion' => 'required|string|max:1000',
        ]);

        try {
            // 获取申诉记录并检查权限
            $appeal = ArbitrationAppeal::with('reviewResult')
                ->where('student_id', auth()->id())
                ->findOrFail($id);

            // 检查申诉是否可以处理
            if ($appeal->status === 'completed') {
                throw new \Exception('该申诉已处理完成，无法再次处理');
            }

            \DB::transaction(function () use ($appeal, $request) {
                // 更新申诉状态
                $appeal->update([
                    'status' => 'completed',
                    'result' => $request->result,
                    'arbitration_opinion' => $request->arbitration_opinion,
                    'processed_at' => now(),
                ]);

                // 更新关联的评审结果状态
                if ($appeal->reviewResult) {
                    $appeal->reviewResult->update([
                        'manual_review_status' => $request->result,
                        'manual_review_comment' => $request->arbitration_opinion
                    ]);
                }
            });

            return response()->json([
                'message' => '申诉处理成功',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => '申诉处理失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}