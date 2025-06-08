<?php

namespace App\Http\Controllers\Task;

use App\Domain\Task\Task;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Gate;

class CreateTaskController extends Controller
{
    public function __invoke(CreateTaskRequest $request): TaskResource
    {
        Gate::authorize('create', Task::class);

        $validatedTask = $request->validated();
        $validatedTask['created_by'] = auth()->id();
        $task = Task::create($validatedTask);
        return new TaskResource($task);
    }
}
