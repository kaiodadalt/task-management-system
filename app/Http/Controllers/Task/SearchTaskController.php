<?php

namespace App\Http\Controllers\Task;

use App\Domain\Task\Task;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTaskRequest;
use App\Http\Resources\TaskCollection;
use Carbon\CarbonImmutable;

class SearchTaskController extends Controller
{
    public function __invoke(SearchTaskRequest $request): TaskCollection
    {
        $tasks = Task::query()
            ->status(TaskStatus::tryFrom($request->status))
            ->priority(TaskPriority::tryFrom($request->priority))
            ->createdAtBetween(CarbonImmutable::parse($request->start_date), $request->end_date)
            ->forUser(auth()->id())
            ->paginate(
                $request->input('per_page', 10), // default to 10
                ['*'],
                'page',
                $request->input('page', 1)
            );
        return new TaskCollection($tasks);
    }
}
