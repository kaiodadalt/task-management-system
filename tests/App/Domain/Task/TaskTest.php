<?php

use App\Domain\Task\Events\TaskSaved;
use App\Domain\Task\Task;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Domain\User\User;

beforeEach(function () {
    Event::fake([TaskSaved::class]);
    Queue::fake();
});

test('can create a task', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();

    $task = Task::create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
        'title' => 'Test Task',
        'description' => 'This is a test task',
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
        'due_date' => now()->addDays(7),
    ]);

    expect($task)->toBeInstanceOf(Task::class)
        ->and($task->title)->toBe('Test Task')
        ->and($task->description)->toBe('This is a test task')
        ->and($task->status)->toBe(TaskStatus::PENDING)
        ->and($task->priority)->toBe(TaskPriority::MEDIUM)
        ->and($task->due_date->format('Y-m-d'))->toBe(now()->addDays(7)->format('Y-m-d'));
});
