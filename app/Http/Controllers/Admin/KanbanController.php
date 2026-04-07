<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KanbanBoard;
use App\Models\KanbanColumn;
use App\Models\KanbanTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function board(Request $request, string $type)
    {
        if (!$request->expectsJson()) return view('admin.kanban.generic', compact('type'));
        $board = KanbanBoard::firstOrCreate(
            ['type' => $type],
            ['name' => ucfirst($type), 'active' => true]
        );

        $columns = KanbanColumn::with([
            'tasks' => fn($q) => $q->with(['assignee:id,name,avatar', 'creator:id,name'])
                                    ->orderBy('sort_order'),
        ])->where('kanban_board_id', $board->id)->orderBy('sort_order')->get();

        return response()->json(['board' => $board, 'columns' => $columns]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $request->validate([
            'kanban_column_id' => 'required|exists:kanban_columns,id',
            'title'            => 'required|string|max:255',
        ]);

        $task = KanbanTask::create([
            'kanban_column_id' => $request->kanban_column_id,
            'title'            => $request->title,
            'description'      => $request->description,
            'priority'         => $request->priority ?? 'medium',
            'assigned_to'      => $request->assigned_to,
            'due_date'         => $request->due_date,
            'tags'             => $request->tags,
            'related_type'     => $request->related_type,
            'related_id'       => $request->related_id,
            'created_by'       => auth('admin')->id(),
        ]);

        return response()->json(['message' => 'Tarefa criada!', 'task' => $task->load('assignee:id,name,avatar')], 201);
    }

    public function moveTask(Request $request, KanbanTask $task): JsonResponse
    {
        $request->validate([
            'kanban_column_id' => 'required|exists:kanban_columns,id',
            'sort_order'       => 'nullable|integer',
        ]);

        $task->update([
            'kanban_column_id' => $request->kanban_column_id,
            'sort_order'       => $request->sort_order ?? 0,
        ]);

        return response()->json(['message' => 'Tarefa movida!', 'task' => $task]);
    }

    public function destroyTask(KanbanTask $task): JsonResponse
    {
        $task->delete();
        return response()->json(['message' => 'Tarefa excluída!']);
    }
}
