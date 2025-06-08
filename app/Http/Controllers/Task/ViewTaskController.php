<?php

namespace App\Http\Controllers\Task;

use App\Domain\Task\Task;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Gate;

class ViewTaskController extends Controller
{
    public function __invoke(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }
}
