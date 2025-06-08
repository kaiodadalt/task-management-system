<?php


namespace Tests\Feature\Controllers;

use App\Domain\Task\Task;
use App\Domain\User\User;
use Symfony\Component\HttpFoundation\Response;


test('task creator can view their task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('tasks.view', $task->id));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('task.id', $task->id);
});

test('task assignee can view assigned task', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    $response = $this->actingAs($assignee)
        ->getJson(route('tasks.view', $task->id));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('task.id', $task->id);
});

test('unauthorized user cannot view task', function () {
    $creator = User::factory()->create();
    $randomUser = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
    ]);

    $response = $this->actingAs($randomUser)
        ->getJson(route('tasks.view', $task->id));

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

test('guest cannot view task', function () {
    $task = Task::factory()->create();

    $response = $this->getJson(route('tasks.view', $task->id));

    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
});

test('returns 404 for non-existent task', function () {
    $user = User::factory()->create();
    $nonExistentTaskId = 999;

    $response = $this->actingAs($user)
        ->getJson(route('tasks.view', $nonExistentTaskId));

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

test('returned task has correct structure', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('tasks.view', $task->id));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonStructure([
            'task' => [
                'id',
                'title',
                'description',
                'status',
                'priority',
                'due_date',
                'created_by',
                'created_at',
                'updated_at',
            ]
        ]);
});

test('task includes assigned_to when present', function () {
    $creator = User::factory()->create();
    $assignee = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    $response = $this->actingAs($creator)
        ->getJson(route('tasks.view', $task->id));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('task.assigned_to', $assignee->id);
});

test('task does not include assigned_to when null', function () {
    $creator = User::factory()->create();

    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($creator)
        ->getJson(route('tasks.view', $task->id));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonMissing(['assigned_to']);
});
