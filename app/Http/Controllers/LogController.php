<?php

namespace App\Http\Controllers;

use App\Models\OperationLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationLog::with('user')
            ->when($request->module, function ($q) use ($request) {
                return $q->where('module', $request->module);
            })
            ->when($request->action, function ($q) use ($request) {
                return $q->where('action', $request->action);
            })
            ->when($request->user_id, function ($q) use ($request) {
                return $q->where('user_id', $request->user_id);
            })
            ->when($request->date_range, function ($q) use ($request) {
                $dates = explode(',', $request->date_range);
                return $q->whereBetween('created_at', $dates);
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->where('description', 'like', '%' . $request->search . '%');
            });

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15),
            'modules' => OperationLog::distinct('module')->pluck('module'),
            'actions' => OperationLog::distinct('action')->pluck('action'),
        ]);
    }
} 