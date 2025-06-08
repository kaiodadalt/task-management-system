<?php

namespace App\Domain\Task\Observers;

use App\Domain\Task\Events\TaskCreated;
use App\Domain\Task\Task;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TaskObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Task $task): void
    {
        TaskCreated::dispatch($task);
    }

    public function updated(Task $task): void
    {
        // TODO: implement this
    }

    public function deleted(Task $task): void
    {
        // TODO: implement this
    }
}
