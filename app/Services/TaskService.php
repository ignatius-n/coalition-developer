<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TaskService
{
    /**
     * Reorder tasks according to their position in the list
     *
     * @param  array<int, int>  $taskIds
     */
    public function reorderTasks(array $taskIds, ?int $projectId = null): void
    {
        $taskIds = array_map('intval', $taskIds);
        if ($taskIds === []) {
            return;
        }

        if (count($taskIds) !== count(array_unique($taskIds))) {
            throw new InvalidArgumentException('Task IDs must be unique.');
        }

        DB::transaction(function () use ($taskIds, $projectId): void {
            $query = Task::query()->whereKey($taskIds)->lockForUpdate();

            if ($projectId !== null) {
                $query->where('project_id', $projectId);
            }

            $tasks = $query->get();

            if ($tasks->count() !== count($taskIds)) {
                throw new InvalidArgumentException('The supplied tasks do not match the selected task scope.');
            }

            foreach ($taskIds as $index => $taskId) {
                $tasks->firstWhere('id', $taskId)->update(['order' => $index + 1]);
            }
        });
    }

    public function normalize(?int $projectId): void
    {
        DB::transaction(function () use ($projectId): void {
            $tasks = Task::query()->where('project_id', $projectId)->orderBy('order')->orderBy('id')->lockForUpdate()->get();
            foreach ($tasks as $index => $task) {
                $task->update(['order' => $index + 1]);
            }
        });
    }

    public function moveToProject(Task $task, ?int $projectId): void
    {
        DB::transaction(function () use ($task, $projectId): void {
            $oldProjectId = $task->project_id;
            if ($oldProjectId === $projectId) {
                return;
            }

            $task->update(['project_id' => $projectId]);
            $this->normalize($oldProjectId);

            $newOrder = Task::query()->where('project_id', $projectId)->where('id', '!=', $task->id)->max('order') ?? 0;

            $task->update(['order' => $newOrder + 1]);
        });
    }
}
