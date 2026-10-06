<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * @return Collection<int, Project>
     */
    public function index(Request $request): Collection
    {
        $query = Project::with('client');

        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('client_id') && $request->client_id !== 'All') {
            $query->where('client_id', $request->client_id);
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

        return $query->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Project
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:Planning,In Progress,On Hold,Completed,Canceled'],
            'priority' => ['required', 'string', 'in:Low,Medium,High,Urgent'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],

        ]);
        $project = Project::create($data);

        return $project;
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): Project
    {
        return $project->load('client', 'tasks');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project): Project
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:Planning,In Progress,On Hold,Completed,Canceled'],
            'priority' => ['required', 'string', 'in:Low,Medium,High,Urgent'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);
        $project->update($data);

        return $project->load('client', 'tasks');
    }

    public function archive(Project $project): JsonResponse
    {
        $project->update([
            'archived_at' => now(),
        ]);

        return response()->json([
            'message' => 'Project archived successfully',
        ]);
    }

    public function restore(Project $project): JsonResponse
    {
        $project->update([
            'archived_at' => null,
        ]);

        return response()->json([
            'message' => 'Project restored successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'message' => 'Project deleted succesfully',
        ]);
    }
}
