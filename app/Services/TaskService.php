<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TaskService
{
    /**
     * Reorder tasks according to their position in the list
     *
     * @param array<int, int> $taskIds
     */
    public function reorderTasks(array $taskIds = [], ?int $projectId = null): void
    {
        try {
            $taskIds    = array_map('intval', $taskIds);
            if ($taskIds === []) {
                return;
            }
            if (count($taskIds) !== count(array_unique($taskIds))) {
                throw new InvalidArgumentException('Task IDs must be unique.');
            }
            DB::transaction(static function () use ($taskIds, $projectId): void {
                $query  = Task::query()->whereKey($taskIds)->lockForUpdate();

                if ($projectId !== null) {
                    $query->where('project_id', $projectId);
                }

                $tasks  = $query->get();

                if ($tasks->count() !== count($taskIds)) {
                    throw new InvalidArgumentException('The supplied tasks do not match the selected task scope.');
                }

                foreach ($taskIds as $index => $taskId) {
                    $tasks->firstWhere('id', $taskId)->update(['order' => $index + 1]);
                }
            });
        }
        catch (\Throwable $e) {
            throw new InvalidArgumentException('Failed to reorder tasks: ' . $e->getMessage(), 0, $e);
        }
    }
}
