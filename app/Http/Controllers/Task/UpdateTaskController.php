<?php

namespace App\Http\Controllers\Task;

use App\Domain\Task\Task;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateTaskController extends Controller
{
    public function __invoke(Task $task, UpdateTaskRequest $request): TaskResource
    {
        Gate::authorize('update', $task);

        $validatedData = $request->validated();
        $task->fill($validatedData);
        DB::transaction(fn() => $task->save());
        $task->load('assignee');
        return new TaskResource($task);
    }
}
