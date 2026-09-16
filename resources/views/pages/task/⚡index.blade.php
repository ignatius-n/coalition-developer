<?php

use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url]
    public ?int $project = null;
    public bool $showForm = false;
    public ?int $editingTaskId = null;
    public string $name = '';
    public ?int $projectId = null;

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Project::query()->orderBy('name')->get();
    }

    #[Computed]
    public function selectedProject(): ?Project
    {
        if ($this->project === null) {
            return null;
        }
        return $this->projects->firstWhere('id', $this->project);
    }

    /**
     * @return Collection<int, Task>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return Task::query()
            ->with('project')
            ->when($this->project, fn($query) => $query->where('project_id', $this->project))
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    public function createTask(): void
    {
        $this->resetForm();
        $this->projectId = $this->project;
        $this->showForm = true;
    }

    public function editTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        $this->editingTaskId = $task->id;
        $this->name = $task->name;
        $this->projectId = $task->project_id;

        $this->resetValidation();
        $this->showForm = true;
    }

    public function saveTask(TaskService $taskService): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'projectId' => ['nullable', 'integer', Rule::exists('projects', 'id')],
        ]);

        if ($this->editingTaskId !== null) {
            $task = Task::findOrFail($this->editingTaskId);
            $oldProjectId = $task->project_id;

            $task->update(['name' => $validated['name']]);

            if ($oldProjectId !== $validated['projectId']) {
                $taskService->moveToProject($task->fresh(), $validated['projectId']);
            }
        }
        else {
            $order = Task::query()->where('project_id', $validated['projectId'])->max('order') ?? 0;
            Task::create([
                'name' => $validated['name'],
                'project_id' => $validated['projectId'],
                'order' => $order + 1,
            ]);
        }

        $this->closeForm();
    }

    public function deleteTask(int $taskId, TaskService $taskService): void
    {
        $task = Task::findOrFail($taskId);
        $projectId = $task->project_id;
        $task->delete();
        $taskService->normalize($projectId);
    }

    /**
     * @param array<int, int|string> $taskIds
     */
    public function reorder(array $taskIds, TaskService $taskService): void
    {
        $taskService->reorderTasks(array_map('intval', $taskIds), $this->project);
        unset($this->tasks);
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingTaskId', 'name', 'projectId']);
        $this->resetValidation();
    }
};
?>

<div class="min-h-screen bg-gray-50 py-10 dark:bg-gray-950">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Coalition Task Manager / Ignatius N</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage and prioritize your tasks.</p>
            </div>

            <button type="button" wire:click="createTask"
                    class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                + New Task
            </button>
        </div>


        @if ($showForm)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true"
                 aria-labelledby="task-form-title">
                <div class="absolute inset-0 bg-black/50" wire:click="closeForm"></div>

                <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
                    <div class="mb-6">
                        <h2 id="task-form-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $editingTaskId ? 'Edit Task' : 'Create Task' }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $editingTaskId ? 'Update the task details.' : 'Add a new task to your project.' }}
                        </p>
                    </div>

                    <form wire:submit="saveTask" class="space-y-5">
                        <div>
                            <label for="task-name"
                                   class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Task name
                            </label>

                            <input
                                id="task-name"
                                type="text"
                                wire:model="name"
                                autofocus
                                class="block w-full rounded-lg border-gray-300 px-3 py-2
                               text-sm shadow-sm focus:border-gray-500
                               focus:ring-gray-500 dark:border-gray-700
                               dark:bg-gray-800 dark:text-white"
                                placeholder="e.g. Fix checkout bug"
                            >

                            @error('name')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        <div>
                            <label for="task-project"
                                   class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Project
                            </label>

                            <select
                                id="task-project"
                                wire:model="projectId"
                                class="block w-full rounded-lg border-gray-300 px-3 py-2
                               text-sm shadow-sm focus:border-gray-500
                               focus:ring-gray-500 dark:border-gray-700
                               dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">No Project</option>
                                @foreach ($this->projects as $project)
                                    <option value="{{ $project->id }}">
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('projectId')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                wire:click="closeForm"
                                class="rounded-lg border border-gray-300 px-4 py-2
                               text-sm font-medium text-gray-700
                               hover:bg-gray-50 dark:border-gray-700
                               dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                class="rounded-lg bg-gray-900 px-4 py-2 text-sm
                               font-medium text-white hover:bg-gray-700
                               disabled:cursor-not-allowed disabled:opacity-50
                               dark:bg-white dark:text-gray-900"
                            >
                                <span wire:loading.remove
                                      wire:target="saveTask">{{ $editingTaskId ? 'Save Changes' : 'Create Task' }}</span>
                                <span wire:loading wire:target="saveTask">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif


        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-smdark:border-gray-800 dark:bg-gray-900">
            <label for="project" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Project
            </label>

            <select id="project" wire:model.live="project"
                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">All Projects</option>
                @foreach ($this->projects as $project)
                    <option value="{{ $project->id }}">
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">

            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <h2 class="font-medium text-gray-900 dark:text-white">
                        Tasks
                    </h2>

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->tasks->count() }}
                        {{ str('task')->plural($this->tasks->count()) }}
                    </span>
                </div>
            </div>

            <div x-data="{
                    sortable: null,
                    init() {
                        this.sortable = Sortable.create(this.$refs.taskList, {
                            animation: 150,
                            handle: '[data-drag-handle]',
                            ghostClass: 'opacity-50',
                            onEnd: () => {
                                const taskIds = Array.from(this.$refs.taskList.querySelectorAll('[data-task-id]'))
                                .map(element => Number(element.dataset.taskId));
                                $wire.reorder(taskIds);
                            },
                        });
                    },
                }"
            >
                <div x-ref="taskList" class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white">
                    @forelse ($this->tasks as $task)
                        <div wire:key="task-{{ $task->id }}" data-task-id="{{ $task->id }}" class="flex items-center gap-4 p-4">
                            <button type="button" data-drag-handle class="cursor-grab text-gray-400 hover:text-gray-600 active:cursor-grabbing" aria-label="Reorder task">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.-->
                                    <path
                                        d="M342.6 73.4C330.1 60.9 309.8 60.9 297.3 73.4L233.3 137.4C224.1 146.6 221.4 160.3 226.4 172.3C231.4 184.3 243.1 192 256 192L288 192L288 288L192 288L192 256C192 243.1 184.2 231.4 172.2 226.4C160.2 221.4 146.5 224.2 137.3 233.3L73.3 297.3C60.8 309.8 60.8 330.1 73.3 342.6L137.3 406.6C146.5 415.8 160.2 418.5 172.2 413.5C184.2 408.5 192 396.9 192 384L192 352L288 352L288 448L256 448C243.1 448 231.4 455.8 226.4 467.8C221.4 479.8 224.2 493.5 233.3 502.7L297.3 566.7C309.8 579.2 330.1 579.2 342.6 566.7L406.6 502.7C415.8 493.5 418.5 479.8 413.5 467.8C408.5 455.8 396.9 448 384 448L352 448L352 352L448 352L448 384C448 396.9 455.8 408.6 467.8 413.6C479.8 418.6 493.5 415.8 502.7 406.7L566.7 342.7C579.2 330.2 579.2 309.9 566.7 297.4L502.7 233.4C493.5 224.2 479.8 221.5 467.8 226.5C455.8 231.5 448 243.1 448 256L448 288L352 288L352 192L384 192C396.9 192 408.6 184.2 413.6 172.2C418.6 160.2 415.8 146.5 406.7 137.3L342.7 73.3z"/>
                                </svg>
                            </button>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900">{{ $task->name }}</p>
                                @if ($task->project)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $task->project->name }}</p>
                                @endif
                            </div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">#{{ $task->order }}</div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="editTask({{ $task->id }})" class="rounded-md px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
                                    Edit
                                </button>
                                <button type="button" wire:click="deleteTask({{ $task->id }})" wire:confirm="Are you sure you want to delete this task?" class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No tasks found.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
