<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * @return Collection<int, Task>
     */
    public function index(Request $request): Collection
    {
        $query = Task::query();

        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('project_id') && $request->project_id !== 'All') {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('user_id') && $request->user_id !== 'All') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') && $request->priority !== 'All') {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->due_date);
        }

        return $query->with('project', 'user')->get();
    }

    public function store(Request $request): Task
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'min:3'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:Todo,In Progress,Completed,Canceled'],
            'priority' => ['required', 'string', 'in:Low,Medium,High,Urgent'],
            'due_date' => ['nullable', 'date'],
        ]);
        $task = Task::create($data);

        return $task;
    }

    public function show(Task $task): Task
    {
        return $task->load('project', 'user');
    }

    public function update(Request $request, Task $task): Task
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'min:3'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:Todo,In Progress,Completed,Canceled'],
            'priority' => ['required', 'string', 'in:Low,Medium,High,Urgent'],
            'due_date' => ['nullable', 'date'],
        ]);
        $task->update($data);

        return $task->load('project', 'user');
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Task Deleted']);
    }

    public function archive(Task $task): Task
    {
        $task->update([
            'archived_at' => now(),
        ]);

        return $task;
    }

    public function restore(Task $task): Task
    {
        $task->update([
            'archived_at' => null,
        ]);

        return $task;
    }
}
