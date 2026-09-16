<?php

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('renders the task manager', function () {
    Project::factory()->create(['name' => 'Website Redesign']);
    Livewire::test('tasks.index')->assertSuccessful()->assertSee('Website Redesign');
});

it('creates a task', function () {
    $project    = Project::factory()->create();

    Livewire::test('tasks.index')
        ->set('name', 'Build dashboard')
        ->set('projectId', $project->id)
        ->call('saveTask')
        ->assertSet('showForm', false);

    $task       = Task::query()->first();

    expect($task)->name->toBe('Build dashboard')->project_id->toBe($project->id)->order->toBe(1);
});

it('appends a new task after existing tasks', function () {
    $project    = Project::factory()->create();

    Task::factory()->for($project)->create(['order' => 1]);
        Task::factory()->for($project)->create(['order' => 2]);

    Livewire::test('tasks.index')
        ->set('name', 'Third task')
        ->set('projectId', $project->id)
        ->call('saveTask');

    expect(
        Task::query()
            ->where('project_id', $project->id)
            ->where('name', 'Third task')
            ->value('order')
    )->toBe(3);
});

it('updates a task', function () {
    $project    = Project::factory()->create();
    $task       = Task::factory()->for($project)->create(['name' => 'Old name', 'order' => 1]);

    Livewire::test('tasks.index')
        ->call('editTask', $task->id)
        ->set('name', 'Updated name')
        ->set('projectId', $project->id)
        ->call('saveTask');

    expect($task->fresh()->name)->toBe('Updated name');
});

it('deletes a task and normalizes remaining orders', function () {
    $project    = Project::factory()->create();
    $tasks      = Task::factory()
        ->for($project)
        ->createMany([
            ['order' => 1],
            ['order' => 2],
            ['order' => 3],
        ]);

    Livewire::test('tasks.index')->call('deleteTask', $tasks[1]->id);

    expect(Task::find($tasks[1]->id))->toBeNull()
        ->and($tasks[0]->fresh()->order)->toBe(1)
        ->and($tasks[2]->fresh()->order)->toBe(2);
});

it('reorders tasks through the component', function () {
    $project    = Project::factory()->create();
    $tasks      = Task::factory()
        ->for($project)
        ->createMany([
            ['order' => 1],
            ['order' => 2],
            ['order' => 3],
        ]);

    Livewire::test('tasks.index')
        ->set('project', $project->id)
        ->call('reorder', [
            $tasks[2]->id,
            $tasks[0]->id,
            $tasks[1]->id,
        ]);

    expect($tasks[2]->fresh()->order)->toBe(1)
        ->and($tasks[0]->fresh()->order)->toBe(2)
        ->and($tasks[1]->fresh()->order)->toBe(3);
});
