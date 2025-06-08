<?php

use App\Domain\Task\Events\TaskDeleted;
use App\Domain\Task\Events\TaskSaved;
use App\Domain\Task\Task;
use App\Domain\Task\TaskPriority;
use App\Domain\Task\TaskStatus;
use App\Domain\User\User;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Event::fake([TaskSaved::class]);
    Event::fake([TaskDeleted::class]);
    Queue::fake();
    $this->creator = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->task = Task::factory()->create([
        'created_by' => $this->creator->id,
        'title' => 'Test Task',
        'description' => 'Task to be deleted',
        'status' => TaskStatus::PENDING,
        'priority' => TaskPriority::MEDIUM,
    ]);
});


test('task owner can delete their task', function () {
    Sanctum::actingAs($this->creator);

    $response = $this->deleteJson(route('tasks.delete', $this->task));

    $response->assertNoContent();

    $this->assertModelMissing($this->task);
});

test('unauthorized user cannot delete task', function () {
    Sanctum::actingAs($this->otherUser);

    $response = $this->deleteJson(route('tasks.delete', $this->task));

    $response->assertStatus(Response::HTTP_FORBIDDEN);
    $this->assertModelExists($this->task);
});

test('unauthenticated user cannot delete task', function () {
    $response = $this->deleteJson(route('tasks.delete', $this->task));

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    $this->assertModelExists($this->task);
});

test('returns 404 for non-existent task', function () {
    Sanctum::actingAs($this->creator);

    $response = $this->deleteJson(route('tasks.delete', 999));

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('assignee cannot delete task', function () {
    $this->task->update(['assigned_to' => $this->otherUser->id]);

    Sanctum::actingAs($this->otherUser);

    $response = $this->deleteJson(route('tasks.delete', $this->task));

    $response->assertStatus(Response::HTTP_FORBIDDEN);
    $this->assertModelExists($this->task);
});

test('deleting task does not affect other tasks', function () {
    // Create another task
    $anotherTask = Task::factory()->create([
        'created_by' => $this->creator->id,
    ]);

    Sanctum::actingAs($this->creator);

    $this->deleteJson(route('tasks.delete', $this->task));

    $this->assertModelMissing($this->task);
    $this->assertModelExists($anotherTask);
});

test('task deleted event is dispatched when task is deleted directly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create([
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'status' => TaskStatus::PENDING->value,
        'priority' => TaskPriority::MEDIUM->value,
    ]);

    $task->delete();

    Event::assertDispatched(TaskDeleted::class, function (TaskDeleted $event) use ($task) {
        return $event->task->id === $task->id &&
            $event->task->title === $task->title &&
            $event->task->created_by === $task->created_by &&
            $event->task->assigned_to === $task->assigned_to;
    });
});
