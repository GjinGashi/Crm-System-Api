<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'clients' => [
                'total' => Client::whereNull('archived_at')->count(),
                'active' => Client::where('status', 'Active')->whereNull('archived_at')->count(),
                'inactive' => Client::where('status', 'Inactive')->whereNull('archived_at')->count(),
                'lead' => Client::where('status', 'Lead')->whereNull('archived_at')->count(),
                'archived' => Client::whereNotNull('archived_at')->count(),
            ],

            'projects' => [
                'total' => Project::whereNull('archived_at')->count(),
                'planning' => Project::where('status', 'Planning')->whereNull('archived_at')->count(),
                'in_progress' => Project::where('status', 'In Progress')->whereNull('archived_at')->count(),
                'on_hold' => Project::where('status', 'On Hold')->whereNull('archived_at')->count(),
                'completed' => Project::where('status', 'Completed')->whereNull('archived_at')->count(),
                'canceled' => Project::where('status', 'Canceled')->whereNull('archived_at')->count(),
                'archived' => Project::whereNotNull('archived_at')->count(),
            ],

            'tasks' => [
                'total' => Task::whereNull('archived_at')->count(),
                'todo' => Task::where('status', 'Todo')->whereNull('archived_at')->count(),
                'in_progress' => Task::where('status', 'In Progress')->whereNull('archived_at')->count(),
                'completed' => Task::where('status', 'Completed')->whereNull('archived_at')->count(),
                'canceled' => Task::where('status', 'Canceled')->whereNull('archived_at')->count(),
                'archived' => Task::whereNotNull('archived_at')->count(),
            ],
        ]);
    }
}
