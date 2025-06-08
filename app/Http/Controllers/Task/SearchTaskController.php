<?php

namespace App\Http\Controllers\Task;

use App\Domain\Task\Task;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTaskRequest;
use App\Http\Resources\TaskCollection;

class SearchTaskController extends Controller
{
    public function __invoke(SearchTaskRequest $request): TaskCollection
    {
        $tasks = Task::query()
            ->status(TaskStatus::tryFrom($request->status))
            ->priority(TaskPriority::tryFrom($request->priority))
            ->createdAtBetween($request->start_date, $request->end_date)
            ->forUser(auth()->id())
            ->paginate(
                perPage: $request->input('per_page', default: 10),
                page: $request->input('page', default: 1)
            );
        return new TaskCollection($tasks);
    }
}
