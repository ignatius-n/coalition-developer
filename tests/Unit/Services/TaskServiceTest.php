<?php

use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    app(TaskService::class)->reorderTasks(
        [
            $tasks[2]->id,
            $tasks[0]->id,
            $tasks[1]->id,
        ],
        $project->id
    );

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

    app(TaskService::class)->reorderTasks(
        [
            $tasks[0]->id,
            $tasks[0]->id,
        ],
        $project->id
    );
})->throws(InvalidArgumentException::class);

it('does not allow tasks from another project to be reordered', function () {
    $projectA           = Project::factory()->create();
    $projectB           = Project::factory()->create();
    $taskA              = Task::factory()->for($projectA)->create(['order' => 1]);
    $taskB              = Task::factory()->for($projectB)->create(['order' => 1]);

    app(TaskService::class)->reorderTasks([$taskA->id, $taskB->id], $projectA->id);
})->throws(InvalidArgumentException::class);

it('normalizes priorities after a task is deleted', function () {
    $project            = Project::factory()->create();
    $tasks              = Task::factory()
        ->for($project)
        ->createMany([
            ['order'    => 1],
            ['order'    => 2],
            ['order'    => 3],
        ]);

    $tasks[1]->delete();

    app(TaskService::class)->normalize($project->id);

    expect($tasks[0]->fresh()->order)->toBe(1)->and($tasks[2]->fresh()->order)->toBe(2);
});

it('moves a task to another project and appends it', function () {
    $source             = Project::factory()->create();
    $destination        = Project::factory()->create();

    $sourceTasks        = Task::factory()
        ->for($source)
        ->createMany([
            ['order'    => 1],
            ['order'    => 2],
            ['order'    => 3],
        ]);

    $destinationTask    = Task::factory()->for($destination)->create(['order' => 1]);

    app(TaskService::class)->moveToProject($sourceTasks[1], $destination->id);

    expect($sourceTasks[0]->fresh()->order)->toBe(1)
        ->and($sourceTasks[2]->fresh()->order)->toBe(2)
        ->and($sourceTasks[1]->fresh()->project_id)->toBe($destination->id)
        ->and($sourceTasks[1]->fresh()->order)->toBe(2)
        ->and($destinationTask->fresh()->order)->toBe(1);
});

it('can move a task into an empty project', function () {
    $source             = Project::factory()->create();
    $destination        = Project::factory()->create();

    $task               = Task::factory()->for($source)->create(['order' => 1]);

    app(TaskService::class)->moveToProject($task, $destination->id);

    expect($task->fresh()->project_id)->toBe($destination->id)->and($task->fresh()->order)->toBe(1);
});

it('can move an unassigned task into a project', function () {
    $project            = Project::factory()->create();
    $task               = Task::factory()->create(['project_id' => null, 'order' => 1]);

    app(TaskService::class)->moveToProject($task, $project->id);

    expect($task->fresh()->project_id)->toBe($project->id)->and($task->fresh()->order)->toBe(1);
});
