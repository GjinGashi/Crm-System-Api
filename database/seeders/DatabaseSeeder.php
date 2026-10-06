<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'john.smith@crm-demo.test'],
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'password' => 'DemoPassword123!',
                'role' => 'admin',
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'sarah.johnson@crm-demo.test'],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'password' => 'DemoPassword123!',
                'role' => 'user',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'michael.brown@crm-demo.test'],
            [
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'password' => 'DemoPassword123!',
                'role' => 'user',
            ]
        );

        $client1 = Client::firstOrCreate(
            ['email' => 'contact@acme.test'],
            [
                'first_name' => 'James',
                'last_name' => 'Anderson',
                'phone' => '+1 555 0101',
                'company' => 'Acme Solutions',
                'address' => '123 Main Street',
                'city' => 'New York',
                'country' => 'USA',
                'status' => 'Active',
                'notes' => 'Long-term business client.',
            ]
        );

        $client2 = Client::firstOrCreate(
            ['email' => 'hello@technova.test'],
            [
                'first_name' => 'Emily',
                'last_name' => 'Wilson',
                'phone' => '+1 555 0102',
                'company' => 'TechNova',
                'address' => '45 Market Road',
                'city' => 'Chicago',
                'country' => 'USA',
                'status' => 'Lead',
                'notes' => 'Potential new technology client.',
            ]
        );

        $client3 = Client::firstOrCreate(
            ['email' => 'info@greenvalley.test'],
            [
                'first_name' => 'Daniel',
                'last_name' => 'Taylor',
                'phone' => '+1 555 0103',
                'company' => 'Green Valley Design',
                'address' => '78 Oak Avenue',
                'city' => 'Boston',
                'country' => 'USA',
                'status' => 'Active',
                'notes' => 'Design and marketing client.',
            ]
        );

        $projects = [
            [
                'client' => $client1,
                'name' => 'Website Redesign',
                'description' => 'Redesign the company website and improve the user experience.',
                'status' => 'In Progress',
                'priority' => 'High',
                'start_date' => '2026-09-01',
                'due_date' => '2026-10-15',
                'budget' => 5000,
            ],
            [
                'client' => $client1,
                'name' => 'CRM Migration',
                'description' => 'Move existing customer data into the new CRM system.',
                'status' => 'Planning',
                'priority' => 'Urgent',
                'start_date' => '2026-09-10',
                'due_date' => '2026-10-30',
                'budget' => 7500,
            ],
            [
                'client' => $client2,
                'name' => 'Mobile App Development',
                'description' => 'Develop a mobile application for customer account management.',
                'status' => 'Planning',
                'priority' => 'High',
                'start_date' => '2026-09-15',
                'due_date' => '2026-12-15',
                'budget' => 12000,
            ],
            [
                'client' => $client3,
                'name' => 'Marketing Campaign',
                'description' => 'Plan and launch a new digital marketing campaign.',
                'status' => 'On Hold',
                'priority' => 'Medium',
                'start_date' => '2026-08-20',
                'due_date' => '2026-10-05',
                'budget' => 3500,
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::firstOrCreate(
                [
                    'client_id' => $projectData['client']->id,
                    'name' => $projectData['name'],
                ],
                [
                    'description' => $projectData['description'],
                    'status' => $projectData['status'],
                    'priority' => $projectData['priority'],
                    'start_date' => $projectData['start_date'],
                    'due_date' => $projectData['due_date'],
                    'budget' => $projectData['budget'],
                    'archived_at' => null,
                ]
            );

            $tasks = match ($project->name) {
                'Website Redesign' => [
                    ['title' => 'Create wireframes', 'user_id' => $user->id],
                    ['title' => 'Build homepage', 'user_id' => $user2->id],
                ],
                'CRM Migration' => [
                    ['title' => 'Prepare database', 'user_id' => $admin->id],
                    ['title' => 'Import client data', 'user_id' => $user->id],
                ],
                'Mobile App Development' => [
                    ['title' => 'Create app dashboard', 'user_id' => $user2->id],
                    ['title' => 'Implement login screen', 'user_id' => $user->id],
                ],
                'Marketing Campaign' => [
                    ['title' => 'Prepare campaign assets', 'user_id' => $admin->id],
                    ['title' => 'Review campaign plan', 'user_id' => $user2->id],
                ],
                default => [],
            };

            foreach ($tasks as $taskData) {
                Task::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'title' => $taskData['title'],
                    ],
                    [
                        'user_id' => $taskData['user_id'],
                        'description' => 'Demo task for the '.$project->name.' project.',
                        'status' => 'Todo',
                        'priority' => 'Medium',
                        'start_time' => '2026-09-10 09:00:00',
                        'end_time' => '2026-09-10 17:00:00',
                        'due_date' => '2026-09-20',
                    ]
                );
            }
        }
        $this->call([
            RoleSeeder::class,
        ]);
    }
}
