<?php

namespace App\Http\Controllers\Task;

use App\Domain\Task\Task;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class DeleteTaskController extends Controller
{
    public function __invoke(Task $task): Response
    {
        Gate::authorize('delete', $task);
        DB::transaction(fn() => $task->delete());
        return response()->noContent();
    }
}
