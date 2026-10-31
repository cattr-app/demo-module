<?php

namespace Modules\Demo\Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectMemberService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Demo\Support\DemoAccounts;

/**
 * Creates the demo accounts (see DemoAccounts) and a project with tasks for each of them except
 * the administrator. Passwords are random: the accounts are signed in only through the demo login.
 */
class DemoSeeder extends Seeder
{
    private const TASKS_PER_PROJECT = 5;

    public function run(): void
    {
        foreach (DemoAccounts::ACCOUNTS as $key => ['role' => $role, 'project_role' => $projectRole]) {
            $user = User::factory()->create([
                'email' => DemoAccounts::email($key),
                'password' => Str::random(40),
                'role_id' => $role,
            ]);

            if ($key === 'admin') {
                continue;
            }

            $project = Project::factory()
                ->has(
                    Task::factory()->for($user, 'assigned')->hasAttached($user)->count(self::TASKS_PER_PROJECT)
                )
                ->create();

            if ($projectRole) {
                ProjectMemberService::syncMembers($project->id, [$user->id => ['role_id' => $projectRole]]);
            }
        }
    }
}
