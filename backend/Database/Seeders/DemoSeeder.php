<?php

namespace Modules\Demo\Database\Seeders;

use App\Enums\Role;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\ProjectMemberService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Demo\Support\DemoAccounts;
use Modules\Demo\Support\DemoCatalog;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Creates the demo company from DemoCatalog: the login accounts (see DemoAccounts),
 * the team, projects with tasks and comments. Passwords are random: the accounts are signed in only
 * through the demo login. A fixed seed builds the same company on every reset.
 */
class DemoSeeder extends Seeder
{
    private const SEED = 20261031;
    private const CODENAME_TASKS_PER_PROJECT = 2;
    private const SECOND_ASSIGNEE_CHANCE = 20;
    private const CLOSED_TASK_CHANCE = 15;
    private const IMPORTANT_TASK_CHANCE = 20;
    private const COMMENT_CHANCE = 40;

    private Randomizer $random;

    /** @var array<string, array<int, Role>> project key => user ID => project role */
    private array $members = [];

    public function run(): void
    {
        $this->random = new Randomizer(new Mt19937(self::SEED));

        $this->createAccounts();
        $this->createTeam();

        $codenames = $this->codenames(count(DemoCatalog::PROJECTS) * self::CODENAME_TASKS_PER_PROJECT);

        foreach (DemoCatalog::PROJECTS as $key => $project) {
            $this->createProject($key, $project, array_splice($codenames, 0, self::CODENAME_TASKS_PER_PROJECT));
        }
    }

    private function createAccounts(): void
    {
        foreach (DemoAccounts::ACCOUNTS as $key => ['role' => $role, 'project_role' => $projectRole]) {
            $persona = DemoCatalog::ACCOUNT_PERSONAS[$key];

            $user = User::factory()->create([
                'full_name' => $persona['name'],
                'email' => DemoAccounts::email($key),
                'password' => Str::random(40),
                'role_id' => $role,
                // Empty: the interface keeps the language the visitor chose on the login page
                'user_language' => '',
            ]);

            foreach ($persona['projects'] as $project) {
                $this->members[$project][$user->id] = $projectRole ?? Role::USER;
            }
        }
    }

    private function createTeam(): void
    {
        foreach (DemoCatalog::TEAM as $key => $member) {
            $user = User::factory()->create([
                'full_name' => $member['name'],
                'email' => DemoCatalog::teamEmail($key),
                'password' => Str::random(40),
                'role_id' => Role::USER,
                'timezone' => $member['timezone'],
                'user_language' => 'en',
            ]);

            foreach ($member['projects'] as $project) {
                $this->members[$project][$user->id] = Role::USER;
            }
        }
    }

    /** @param string[] $codenames */
    private function createProject(string $key, array $project, array $codenames): void
    {
        $members = $this->members[$key] ?? [];

        $model = Project::factory()->create([
            'company_id' => 1,
            'name' => $project['name'],
            'description' => $project['description'],
            'important' => false,
        ]);

        ProjectMemberService::syncMembers(
            $model->id,
            array_map(static fn (Role $role) => ['role_id' => $role->value], $members)
        );

        // Project auditors watch the project and do not track time on it
        $assignees = array_keys(array_filter($members, static fn (Role $role) => $role !== Role::AUDITOR));
        $assignees = $assignees ? $this->random->shuffleArray($assignees) : [];
        $manager = array_search(Role::MANAGER, $members, true)
            ?: User::firstWhere('email', DemoAccounts::email('admin'))->id;

        $tasks = $project['tasks'];
        foreach ($this->random->pickArrayKeys(DemoCatalog::CODENAME_TASKS, self::CODENAME_TASKS_PER_PROJECT) as $i => $template) {
            [$name, $estimate] = DemoCatalog::CODENAME_TASKS[$template];
            $tasks[] = [str_replace('{name}', $codenames[$i], $name), $estimate];
        }

        $openStatus = Status::where('active', true)->value('id');
        $closedStatus = Status::where('active', false)->value('id') ?? $openStatus;
        $priorities = Priority::orderBy('id')->pluck('id')->all();

        foreach ($tasks as $index => [$name, $estimate]) {
            $task = Task::factory()->create([
                'project_id' => $model->id,
                'task_name' => $name,
                'description' => '',
                'assigned_by' => $manager,
                'important' => $this->chance(self::IMPORTANT_TASK_CHANCE),
                'priority_id' => $priorities[$this->random->getInt(0, count($priorities) - 1)],
                // The first round of the round robin stays open, so everyone has work to track
                'status_id' => $index >= count($assignees) && $this->chance(self::CLOSED_TASK_CHANCE)
                    ? $closedStatus
                    : $openStatus,
                'start_date' => now()->subDays($this->random->getInt(1, 20))->toDateString(),
                'due_date' => now()->addDays($this->random->getInt(-3, 30))->toDateString(),
                'estimate' => $estimate ? $estimate * 3600 : null,
            ]);

            if (!$assignees) {
                continue;
            }

            // Round robin, so everyone in the project gets tasks
            $taskAssignees = [$assignees[$index % count($assignees)]];
            if (count($assignees) > 1 && $this->chance(self::SECOND_ASSIGNEE_CHANCE)) {
                $taskAssignees[] = $assignees[($index + 1) % count($assignees)];
            }

            $task->users()->sync($taskAssignees);

            if ($this->chance(self::COMMENT_CHANCE)) {
                foreach ($this->random->pickArrayKeys(DemoCatalog::COMMENTS, $this->random->getInt(1, 2)) as $comment) {
                    TaskComment::create([
                        'task_id' => $task->id,
                        'user_id' => $taskAssignees[$this->random->getInt(0, count($taskAssignees) - 1)],
                        'content' => DemoCatalog::COMMENTS[$comment],
                    ]);
                }
            }
        }
    }

    /** @return string[] distinct "{adjective} {noun}" code names */
    private function codenames(int $count): array
    {
        $names = [];

        $adjectives = DemoCatalog::CODENAME_ADJECTIVES;
        $nouns = DemoCatalog::CODENAME_NOUNS;

        while (count($names) < $count) {
            $adjective = $adjectives[$this->random->getInt(0, count($adjectives) - 1)];
            $noun = $nouns[$this->random->getInt(0, count($nouns) - 1)];

            // Both lists have "Fjord"
            if ($adjective !== $noun) {
                $names["$adjective $noun"] = "$adjective $noun";
            }
        }

        return array_values($names);
    }

    private function chance(int $percent): bool
    {
        return $this->random->getInt(1, 100) <= $percent;
    }
}
