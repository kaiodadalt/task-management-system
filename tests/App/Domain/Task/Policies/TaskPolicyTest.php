
<?php

use App\Domain\Task\Events\TaskDeleted;
use App\Domain\Task\Events\TaskSaved;
use App\Domain\Task\Task;
use App\Domain\User\User;

beforeEach(function () {
    Event::fake([TaskSaved::class, TaskDeleted::class]);
    Queue::fake();
});

test('user can create a task', function () {
    $user = User::factory()->create();

    expect($user->can('create', Task::class))->toBeTrue();
});

test('task creator can view their task', function () {
    $creator = User::factory()->create();
    $task = Task::factory()->create([
        'created_by' => $creator->id,
    ]);

    expect($creator->can('view', $task))->toBeTrue();
});

test('task assignee can view their assigned task', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    expect($assignee->can('view', $task))->toBeTrue();
});

test('user cannot view task they did not create or are not assigned to', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $randomUser = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    expect($randomUser->can('view', $task))->toBeFalse();
});

test('task creator can update their task', function () {
    $creator = User::factory()->create();
    $task = Task::factory()->create([
        'created_by' => $creator->id,
    ]);

    expect($creator->can('update', $task))->toBeTrue();
});

test('task assignee cannot update a task they are assigned to', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    expect($assignee->can('update', $task))->toBeFalse();
});

test('random user cannot update a task', function () {
    $creator = User::factory()->create();
    $randomUser = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
    ]);

    expect($randomUser->can('update', $task))->toBeFalse();
});

test('task creator can delete their task', function () {
    $creator = User::factory()->create();
    $task = Task::factory()->create([
        'created_by' => $creator->id,
    ]);

    expect($creator->can('delete', $task))->toBeTrue();
});

test('task assignee cannot delete a task they are assigned to', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    expect($assignee->can('delete', $task))->toBeFalse();
});

test('random user cannot delete a task', function () {
    $creator = User::factory()->create();
    $randomUser = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
    ]);

    expect($randomUser->can('delete', $task))->toBeFalse();
});
