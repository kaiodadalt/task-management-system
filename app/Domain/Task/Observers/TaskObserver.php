<?php

namespace App\Domain\Task\Observers;

use App\Domain\Task\Events\TaskDeleted;
use App\Domain\Task\Events\TaskSaved;
use App\Domain\Task\Task;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TaskObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Task $task): void
    {
        TaskSaved::dispatch($task);
    }

    public function updated(Task $task): void
    {
        TaskSaved::dispatch($task);
    }

    public function deleted(Task $task): void
    {
        TaskDeleted::dispatch($task);
    }
}
