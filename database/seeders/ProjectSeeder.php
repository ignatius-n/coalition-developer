<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Random\RandomException;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @throws RandomException
     */
    public function run(): void
    {
        Project::factory()
            ->count(1)
            ->create()
            ->each(function ($project) {
                $project->tasks()->saveMany(Task::factory()->count(random_int(3, 10))->make());
            });
    }
}
