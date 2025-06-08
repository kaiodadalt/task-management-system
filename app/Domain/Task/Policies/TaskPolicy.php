<?php

namespace App\Domain\Task\Policies;

use App\Domain\Task\Task;
use App\Domain\User\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $task->created_by === $user->id || $task->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->created_by === $user->id;
    }
}
