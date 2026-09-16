<?php

use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('assigns priorities according to the supplied task order', function () {
    $project            = Project::factory()->create();
    $tasks              = Task::factory()
        ->for($project)
        ->createMany([
            ['order'    => 1],
            ['order'    => 2],
            ['order'    => 3],
        ]);

    app(TaskService::class)->reorderTasks([
        $tasks[2]->id,
        $tasks[0]->id,
        $tasks[1]->id,
    ], $project->id);

    expect($tasks[2]->fresh()->order)->toBe(1)
        ->and($tasks[0]->fresh()->order)->toBe(2)
        ->and($tasks[1]->fresh()->order)->toBe(3);
});

it('rejects duplicate task ids', function () {
    $project            = Project::factory()->create();
    $tasks              = Task::factory()
        ->for($project)
        ->createMany([
            ['order'    => 1],
            ['order'    => 2],
        ]);

    app(TaskService::class)->reorderTasks([
        $tasks[0]->id,
        $tasks[0]->id,
    ], $project->id);
})->throws(InvalidArgumentException::class);

it('does not allow tasks from another project to be reordered', function () {
    $projectA           = Project::factory()->create();
    $projectB           = Project::factory()->create();
    $taskA              = Task::factory()->for($projectA)->create([
        'order'         => 1,
    ]);

    $taskB              = Task::factory()->for($projectB)->create([
        'order'         => 1,
    ]);

    app(TaskService::class)->reorderTasks([
        $taskA->id,
        $taskB->id,
    ], $projectA->id);
})->throws(InvalidArgumentException::class);
